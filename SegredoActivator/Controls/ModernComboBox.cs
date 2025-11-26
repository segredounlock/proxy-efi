using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Windows.Forms;

namespace SegredoActivator.Controls
{
    /// <summary>
    /// ComboBox moderno com estilo personalizado
    /// </summary>
    public class ModernComboBox : ComboBox
    {
        private Color _borderColor = Color.FromArgb(100, 150, 200);
        private Color _focusBorderColor = Color.FromArgb(0, 122, 204);
        private int _borderRadius = 6;
        private bool _isFocused = false;

        public Color BorderColor
        {
            get => _borderColor;
            set { _borderColor = value; Invalidate(); }
        }

        public Color FocusBorderColor
        {
            get => _focusBorderColor;
            set { _focusBorderColor = value; Invalidate(); }
        }

        public int BorderRadius
        {
            get => _borderRadius;
            set { _borderRadius = value; Invalidate(); }
        }

        public ModernComboBox()
        {
            FlatStyle = FlatStyle.Flat;
            BackColor = Color.White;
            ForeColor = Color.FromArgb(64, 64, 64);
            Font = new Font("Segoe UI", 9.5F);
            DropDownStyle = ComboBoxStyle.DropDownList;
            DrawMode = DrawMode.OwnerDrawFixed;
            ItemHeight = 30;

            GotFocus += (s, e) => { _isFocused = true; Invalidate(); };
            LostFocus += (s, e) => { _isFocused = false; Invalidate(); };
        }

        protected override void OnDrawItem(DrawItemEventArgs e)
        {
            if (e.Index < 0) return;

            e.Graphics.SmoothingMode = SmoothingMode.AntiAlias;

            // Background
            bool isSelected = (e.State & DrawItemState.Selected) == DrawItemState.Selected;
            Color bgColor = isSelected ? Color.FromArgb(230, 240, 255) : Color.White;

            using (SolidBrush bgBrush = new SolidBrush(bgColor))
            {
                e.Graphics.FillRectangle(bgBrush, e.Bounds);
            }

            // Texto
            Color textColor = isSelected ? Color.FromArgb(0, 122, 204) : Color.FromArgb(64, 64, 64);
            using (SolidBrush textBrush = new SolidBrush(textColor))
            {
                StringFormat sf = new StringFormat
                {
                    LineAlignment = StringAlignment.Center,
                    Alignment = StringAlignment.Near
                };

                Rectangle textRect = new Rectangle(e.Bounds.X + 10, e.Bounds.Y, e.Bounds.Width - 10, e.Bounds.Height);
                e.Graphics.DrawString(Items[e.Index].ToString(), Font, textBrush, textRect, sf);
            }

            e.DrawFocusRectangle();
        }

        protected override void OnPaint(PaintEventArgs e)
        {
            base.OnPaint(e);
            
            Graphics g = e.Graphics;
            g.SmoothingMode = SmoothingMode.AntiAlias;

            // Desenhar borda personalizada
            Color currentBorderColor = _isFocused ? _focusBorderColor : _borderColor;
            using (Pen borderPen = new Pen(currentBorderColor, 2))
            {
                Rectangle rect = new Rectangle(0, 0, Width - 1, Height - 1);
                using (GraphicsPath path = GetRoundedRectangle(rect, _borderRadius))
                {
                    g.DrawPath(borderPen, path);
                }
            }
        }

        private GraphicsPath GetRoundedRectangle(Rectangle bounds, int radius)
        {
            GraphicsPath path = new GraphicsPath();
            int diameter = radius * 2;
            Rectangle arc = new Rectangle(bounds.Location, new Size(diameter, diameter));

            path.AddArc(arc, 180, 90);
            arc.X = bounds.Right - diameter;
            path.AddArc(arc, 270, 90);
            arc.Y = bounds.Bottom - diameter;
            path.AddArc(arc, 0, 90);
            arc.X = bounds.Left;
            path.AddArc(arc, 90, 90);
            path.CloseFigure();
            
            return path;
        }
    }
}
