using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Imaging;
using System.Runtime.InteropServices;
using System.Windows.Forms;

namespace iSkorpionA12
{
    public class Dropshadow : Form
    {
        private Bitmap _shadowBitmap;
        private Color _shadowColor;
        private int _shadowH;
        private byte _shadowOpacity = 90;
        private int _shadowV;

        // Public properties
        public int ShadowBlur { get; set; }
        public int ShadowSpread { get; set; }
        public int ShadowRadius { get; set; }

        public Color ShadowColor
        {
            get { return _shadowColor; }
            set
            {
                _shadowColor = value;
                _shadowOpacity = _shadowColor.A;
            }
        }

        public Bitmap ShadowBitmap
        {
            get { return _shadowBitmap; }
            set
            {
                _shadowBitmap = value;
                SetBitmap(_shadowBitmap, ShadowOpacity);
            }
        }

        public byte ShadowOpacity
        {
            get { return _shadowOpacity; }
            set
            {
                _shadowOpacity = value;
                SetBitmap(ShadowBitmap, _shadowOpacity);
            }
        }

        public int ShadowH
        {
            get { return _shadowH; }
            set
            {
                _shadowH = value;
                RefreshShadow(false);
            }
        }

        public int OffsetX => ShadowH - (ShadowBlur + ShadowSpread);

        public int OffsetY => ShadowV - (ShadowBlur + ShadowSpread);

        public new int Width => Owner.Width + (ShadowSpread + ShadowBlur) * 2;

        public new int Height => Owner.Height + (ShadowSpread + ShadowBlur) * 2;

        public int ShadowV
        {
            get { return _shadowV; }
            set
            {
                _shadowV = value;
                RefreshShadow(false);
            }
        }

        protected override CreateParams CreateParams
        {
            get
            {
                CreateParams cp = base.CreateParams;
                cp.ExStyle |= 0x80000; // WS_EX_LAYERED
                cp.ExStyle |= 0x20;    // WS_EX_TRANSPARENT
                return cp;
            }
        }

        // Constructor that takes 1 argument
        public Dropshadow(Form owner)
        {
            Owner = owner;
            ShadowColor = Color.Black;
            FormBorderStyle = FormBorderStyle.None;
            ShowInTaskbar = false;
            StartPosition = FormStartPosition.Manual;

            // Set default shadow values
            ShadowBlur = 15;
            ShadowSpread = 3;
            ShadowRadius = 3;
            ShadowH = 0;
            ShadowV = 0;

            // Set initial size
            base.Width = Owner.Width + (ShadowSpread + ShadowBlur) * 2;
            base.Height = Owner.Height + (ShadowSpread + ShadowBlur) * 2;

            Owner.LocationChanged += (sender, eventArgs) => UpdateLocation();
            Owner.SizeChanged += (sender, eventArgs) => RefreshShadow();
            Owner.FormClosing += (sender, eventArgs) => Close();
            Owner.VisibleChanged += (sender, eventArgs) =>
            {
                if (Owner != null)
                    Visible = Owner.Visible;
            };
            Owner.Activated += (sender, args) => Owner.BringToFront();

            Load += (sender, e) => RefreshShadow();
        }

        public void UpdateLocation(object sender = null, EventArgs eventArgs = null)
        {
            if (Owner == null) return;

            Point pos = Owner.Location;
            pos.Offset(OffsetX, OffsetY);
            Location = pos;

            Owner.BringToFront();
        }

        public void RefreshShadow(bool redraw = true)
        {
            if (Owner == null) return;

            base.Width = Owner.Width + (ShadowSpread + ShadowBlur) * 2;
            base.Height = Owner.Height + (ShadowSpread + ShadowBlur) * 2;

            if (redraw)
                ShadowBitmap = DrawShadowBitmap(Owner.Width, Owner.Height, 0, ShadowBlur, ShadowSpread, ShadowColor);

            UpdateLocation();

            Region r = Region.FromHrgn(Win32.CreateRoundRectRgn(0, 0, Width, Height, ShadowRadius, ShadowRadius));
            Region ownerRegion = Owner.Region?.Clone() ?? new Region(Owner.ClientRectangle);
            ownerRegion.Translate(-OffsetX, -OffsetY);
            r.Exclude(ownerRegion);
            Region = r;
            Owner.Refresh();
        }

        public void SetBitmap(Bitmap bitmap, byte opacity = 255)
        {
            if (bitmap.PixelFormat != PixelFormat.Format32bppArgb)
                throw new ApplicationException("The bitmap must be 32ppp with alpha-channel.");

            IntPtr screenDc = Win32.GetDC(IntPtr.Zero);
            IntPtr memDc = Win32.CreateCompatibleDC(screenDc);
            IntPtr hBitmap = IntPtr.Zero;
            IntPtr oldBitmap = IntPtr.Zero;

            try
            {
                hBitmap = bitmap.GetHbitmap(Color.FromArgb(0));
                oldBitmap = Win32.SelectObject(memDc, hBitmap);

                Win32.Size size = new Win32.Size(bitmap.Width, bitmap.Height);
                Win32.Point pointSource = new Win32.Point(0, 0);
                Win32.Point topPos = new Win32.Point(Left, Top);

                Win32.BLENDFUNCTION blend = new Win32.BLENDFUNCTION();
                blend.BlendOp = Win32.AC_SRC_OVER;
                blend.BlendFlags = 0;
                blend.SourceConstantAlpha = opacity;
                blend.AlphaFormat = Win32.AC_SRC_ALPHA;

                Win32.UpdateLayeredWindow(Handle, screenDc, ref topPos, ref size, memDc, ref pointSource, 0, ref blend, Win32.ULW_ALPHA);
            }
            finally
            {
                Win32.ReleaseDC(IntPtr.Zero, screenDc);
                if (hBitmap != IntPtr.Zero)
                {
                    Win32.SelectObject(memDc, oldBitmap);
                    Win32.DeleteObject(hBitmap);
                }
                Win32.DeleteDC(memDc);
            }
        }

        public static Bitmap DrawShadowBitmap(int width, int height, int borderRadius, int blur, int spread, Color color)
        {
            int ex = blur + spread;
            int w = width + ex * 2;
            int h = height + ex * 2;
            int solidW = width + spread * 2;
            int solidH = height + spread * 2;

            Bitmap bitmap = new Bitmap(w, h, PixelFormat.Format32bppArgb);

            using (Graphics g = Graphics.FromImage(bitmap))
            using (SolidBrush solidBrush = new SolidBrush(Color.FromArgb(color.A, color.R, color.G, color.B)))
            {
                g.SmoothingMode = SmoothingMode.AntiAlias;
                g.CompositingQuality = CompositingQuality.HighQuality;

                g.FillRectangle(solidBrush, blur, blur, solidW, solidH);

                if (blur > 0)
                {
                    // Left edge
                    using (LinearGradientBrush leftBrush = new LinearGradientBrush(
                        new Point(0, blur),
                        new Point(blur, blur),
                        Color.Transparent,
                        color))
                    {
                        g.FillRectangle(leftBrush, 0, blur, blur, solidH);
                    }

                    // Top edge
                    using (LinearGradientBrush topBrush = new LinearGradientBrush(
                        new Point(blur, 0),
                        new Point(blur, blur),
                        Color.Transparent,
                        color))
                    {
                        g.FillRectangle(topBrush, blur, 0, solidW, blur);
                    }

                    // Right edge
                    using (LinearGradientBrush rightBrush = new LinearGradientBrush(
                        new Point(w - blur, blur),
                        new Point(w, blur),
                        color,
                        Color.Transparent))
                    {
                        g.FillRectangle(rightBrush, w - blur, blur, blur, solidH);
                    }

                    // Bottom edge
                    using (LinearGradientBrush bottomBrush = new LinearGradientBrush(
                        new Point(blur, h - blur),
                        new Point(blur, h),
                        color,
                        Color.Transparent))
                    {
                        g.FillRectangle(bottomBrush, blur, h - blur, solidW, blur);
                    }

                    // Corners
                    DrawBlurredCorners(g, blur, w, h, color);
                }
            }

            return bitmap;
        }

        private static void DrawBlurredCorners(Graphics g, int blur, int totalW, int totalH, Color color)
        {
            // Top-left corner
            using (GraphicsPath topLeftPath = new GraphicsPath())
            {
                topLeftPath.AddEllipse(0, 0, blur * 2, blur * 2);
                using (PathGradientBrush topLeftBrush = new PathGradientBrush(topLeftPath))
                {
                    topLeftBrush.CenterColor = color;
                    topLeftBrush.SurroundColors = new Color[] { Color.Transparent };
                    g.FillPie(topLeftBrush, 0, 0, blur * 2, blur * 2, 180, 90);
                }
            }

            // Top-right corner
            using (GraphicsPath topRightPath = new GraphicsPath())
            {
                topRightPath.AddEllipse(totalW - blur * 2, 0, blur * 2, blur * 2);
                using (PathGradientBrush topRightBrush = new PathGradientBrush(topRightPath))
                {
                    topRightBrush.CenterColor = color;
                    topRightBrush.SurroundColors = new Color[] { Color.Transparent };
                    g.FillPie(topRightBrush, totalW - blur * 2, 0, blur * 2, blur * 2, 270, 90);
                }
            }

            // Bottom-right corner
            using (GraphicsPath bottomRightPath = new GraphicsPath())
            {
                bottomRightPath.AddEllipse(totalW - blur * 2, totalH - blur * 2, blur * 2, blur * 2);
                using (PathGradientBrush bottomRightBrush = new PathGradientBrush(bottomRightPath))
                {
                    bottomRightBrush.CenterColor = color;
                    bottomRightBrush.SurroundColors = new Color[] { Color.Transparent };
                    g.FillPie(bottomRightBrush, totalW - blur * 2, totalH - blur * 2, blur * 2, blur * 2, 0, 90);
                }
            }

            // Bottom-left corner
            using (GraphicsPath bottomLeftPath = new GraphicsPath())
            {
                bottomLeftPath.AddEllipse(0, totalH - blur * 2, blur * 2, blur * 2);
                using (PathGradientBrush bottomLeftBrush = new PathGradientBrush(bottomLeftPath))
                {
                    bottomLeftBrush.CenterColor = color;
                    bottomLeftBrush.SurroundColors = new Color[] { Color.Transparent };
                    g.FillPie(bottomLeftBrush, 0, totalH - blur * 2, blur * 2, blur * 2, 90, 90);
                }
            }
        }

        protected override void OnLoad(EventArgs e)
        {
            base.OnLoad(e);
            RefreshShadow();
        }
    }

    internal static class Win32
    {
        public const int ULW_ALPHA = 0x2;
        public const byte AC_SRC_OVER = 0x0;
        public const byte AC_SRC_ALPHA = 0x1;

        [DllImport("gdi32.dll")]
        public static extern IntPtr CreateRoundRectRgn(int nLeftRect, int nTopRect, int nRightRect, int nBottomRect, int nWidthEllipse, int nHeightEllipse);

        [DllImport("user32.dll", ExactSpelling = true, SetLastError = true)]
        public static extern bool UpdateLayeredWindow(IntPtr hwnd, IntPtr hdcDst, ref Point pptDst, ref Size psize, IntPtr hdcSrc, ref Point pprSrc,
            int crKey, ref BLENDFUNCTION pblend, int dwFlags);

        [DllImport("user32.dll", ExactSpelling = true, SetLastError = true)]
        public static extern IntPtr GetDC(IntPtr hWnd);

        [DllImport("user32.dll", ExactSpelling = true)]
        public static extern int ReleaseDC(IntPtr hWnd, IntPtr hDC);

        [DllImport("gdi32.dll", ExactSpelling = true, SetLastError = true)]
        public static extern IntPtr CreateCompatibleDC(IntPtr hDC);

        [DllImport("gdi32.dll", ExactSpelling = true, SetLastError = true)]
        public static extern bool DeleteDC(IntPtr hdc);

        [DllImport("gdi32.dll", ExactSpelling = true, SetLastError = true)]
        public static extern IntPtr SelectObject(IntPtr hDC, IntPtr hObject);

        [DllImport("gdi32.dll", ExactSpelling = true, SetLastError = true)]
        public static extern bool DeleteObject(IntPtr hObject);

        [StructLayout(LayoutKind.Sequential, Pack = 1)]
        public struct BLENDFUNCTION
        {
            public byte BlendOp;
            public byte BlendFlags;
            public byte SourceConstantAlpha;
            public byte AlphaFormat;
        }

        [StructLayout(LayoutKind.Sequential)]
        public struct Point
        {
            public int x;
            public int y;
            public Point(int x, int y)
            {
                this.x = x;
                this.y = y;
            }
        }

        [StructLayout(LayoutKind.Sequential)]
        public struct Size
        {
            public int cx;
            public int cy;
            public Size(int cx, int cy)
            {
                this.cx = cx;
                this.cy = cy;
            }
        }
    }
}