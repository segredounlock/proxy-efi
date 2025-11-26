using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Windows.Forms;

namespace SegredoActivator.Controls
{
    /// <summary>
    /// Botão moderno com gradiente, sombra e animações
    /// </summary>
    public class ModernButton : Button
    {
        private Color _startColor = Color.FromArgb(0, 122, 204);
        private Color _endColor = Color.FromArgb(0, 150, 255);
        private Color _hoverStartColor = Color.FromArgb(0, 150, 255);
        private Color _hoverEndColor = Color.FromArgb(0, 180, 255);
        private int _cornerRadius = 8;
        private bool _isHovered = false;
        private Timer _animationTimer;
        private float _animationProgress = 0f;

        public Color StartColor
        {
            get => _startColor;
            set { _startColor = value; Invalidate(); }
        }

        public Color EndColor
        {
            get => _endColor;
            set { _endColor = value; Invalidate(); }
        }

        public int CornerRadius
        {
            get => _cornerRadius;
            set { _cornerRadius = value; Invalidate(); }
        }

        public ModernButton()
        {
            FlatStyle = FlatStyle.Flat;
            FlatAppearance.BorderSize = 0;
            FlatAppearance.MouseOverBackColor = Color.Transparent;
            FlatAppearance.MouseDownBackColor = Color.Transparent;
            BackColor = Color.Transparent;
            ForeColor = Color.White;
            Font = new Font("Segoe UI", 10F, FontStyle.Bold);
            Cursor = Cursors.Hand;
            Size = new Size(150, 45);

            _animationTimer = new Timer { Interval = 20 };
            _animationTimer.Tick += AnimationTimer_Tick;

            MouseEnter += (s, e) => { _isHovered = true; _animationTimer.Start(); };
            MouseLeave += (s, e) => { _isHovered = false; _animationTimer.Start(); };
        }

        private void AnimationTimer_Tick(object sender, EventArgs e)
        {
            if (_isHovered)
            {
                _animationProgress += 0.1f;
                if (_animationProgress >= 1f)
                {
                    _animationProgress = 1f;
                    _animationTimer.Stop();
                }
            }
            else
            {
                _animationProgress -= 0.1f;
                if (_animationProgress <= 0f)
                {
                    _animationProgress = 0f;
                    _animationTimer.Stop();
                }
            }
            Invalidate();
        }

        protected override void OnPaint(PaintEventArgs e)
        {
            Graphics g = e.Graphics;
            g.SmoothingMode = SmoothingMode.AntiAlias;
            g.InterpolationMode = InterpolationMode.HighQualityBicubic;
            g.CompositingQuality = CompositingQuality.HighQuality;

            // Cores interpoladas para animação suave
            Color currentStartColor = InterpolateColor(_startColor, _hoverStartColor, _animationProgress);
            Color currentEndColor = InterpolateColor(_endColor, _hoverEndColor, _animationProgress);

            // Criar path com bordas arredondadas
            using (GraphicsPath path = GetRoundedRectangle(ClientRectangle, _cornerRadius))
            {
                // Desenhar sombra
                using (GraphicsPath shadowPath = GetRoundedRectangle(
                    new Rectangle(ClientRectangle.X + 2, ClientRectangle.Y + 2, 
                                  ClientRectangle.Width, ClientRectangle.Height), _cornerRadius))
                {
                    using (SolidBrush shadowBrush = new SolidBrush(Color.FromArgb(50, 0, 0, 0)))
                    {
                        g.FillPath(shadowBrush, shadowPath);
                    }
                }

                // Gradiente do botão
                using (LinearGradientBrush brush = new LinearGradientBrush(
                    ClientRectangle, currentStartColor, currentEndColor, 90F))
                {
                    g.FillPath(brush, path);
                }

                // Borda sutil
                using (Pen borderPen = new Pen(Color.FromArgb(100, 255, 255, 255), 1))
                {
                    g.DrawPath(borderPen, path);
                }
            }

            // Desenhar texto
            TextRenderer.DrawText(g, Text, Font, ClientRectangle, ForeColor,
                TextFormatFlags.HorizontalCenter | TextFormatFlags.VerticalCenter);
        }

        private GraphicsPath GetRoundedRectangle(Rectangle bounds, int radius)
        {
            GraphicsPath path = new GraphicsPath();
            int diameter = radius * 2;
            Rectangle arc = new Rectangle(bounds.Location, new Size(diameter, diameter));

            // Canto superior esquerdo
            path.AddArc(arc, 180, 90);
            
            // Canto superior direito
            arc.X = bounds.Right - diameter;
            path.AddArc(arc, 270, 90);
            
            // Canto inferior direito
            arc.Y = bounds.Bottom - diameter;
            path.AddArc(arc, 0, 90);
            
            // Canto inferior esquerdo
            arc.X = bounds.Left;
            path.AddArc(arc, 90, 90);
            
            path.CloseFigure();
            return path;
        }

        private Color InterpolateColor(Color color1, Color color2, float progress)
        {
            int r = (int)(color1.R + (color2.R - color1.R) * progress);
            int g = (int)(color1.G + (color2.G - color1.G) * progress);
            int b = (int)(color1.B + (color2.B - color1.B) * progress);
            return Color.FromArgb(r, g, b);
        }

        protected override void Dispose(bool disposing)
        {
            if (disposing)
            {
                _animationTimer?.Dispose();
            }
            base.Dispose(disposing);
        }
    }
}
