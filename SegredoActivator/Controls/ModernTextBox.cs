using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Windows.Forms;

namespace SegredoActivator.Controls
{
    /// <summary>
    /// TextBox moderno com borda arredondada e efeitos
    /// </summary>
    public class ModernTextBox : Panel
    {
        private TextBox _textBox;
        private Label _placeholderLabel;
        private Color _borderColor = Color.FromArgb(200, 200, 200);
        private Color _focusBorderColor = Color.FromArgb(0, 122, 204);
        private int _borderRadius = 6;
        private bool _isFocused = false;
        private string _placeholder = "";

        public string Text
        {
            get => _textBox.Text;
            set => _textBox.Text = value;
        }

        public string Placeholder
        {
            get => _placeholder;
            set
            {
                _placeholder = value;
                _placeholderLabel.Text = value;
                UpdatePlaceholderVisibility();
            }
        }

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

        public new Font Font
        {
            get => _textBox.Font;
            set
            {
                _textBox.Font = value;
                _placeholderLabel.Font = value;
            }
        }

        public ModernTextBox()
        {
            BackColor = Color.White;
            Size = new Size(250, 40);
            Padding = new Padding(10, 8, 10, 8);

            // TextBox interno
            _textBox = new TextBox
            {
                BorderStyle = BorderStyle.None,
                Font = new Font("Segoe UI", 10F),
                BackColor = Color.White,
                ForeColor = Color.FromArgb(64, 64, 64),
                Dock = DockStyle.Fill
            };

            // Label de placeholder
            _placeholderLabel = new Label
            {
                Text = "",
                ForeColor = Color.FromArgb(160, 160, 160),
                BackColor = Color.Transparent,
                Font = new Font("Segoe UI", 10F),
                Dock = DockStyle.Fill,
                TextAlign = ContentAlignment.MiddleLeft,
                Cursor = Cursors.IBeam
            };

            Controls.Add(_textBox);
            Controls.Add(_placeholderLabel);
            _placeholderLabel.BringToFront();

            _textBox.TextChanged += (s, e) => UpdatePlaceholderVisibility();
            _textBox.GotFocus += (s, e) => { _isFocused = true; Invalidate(); };
            _textBox.LostFocus += (s, e) => { _isFocused = false; Invalidate(); };
            _placeholderLabel.Click += (s, e) => _textBox.Focus();
        }

        private void UpdatePlaceholderVisibility()
        {
            _placeholderLabel.Visible = string.IsNullOrEmpty(_textBox.Text);
        }

        protected override void OnPaint(PaintEventArgs e)
        {
            base.OnPaint(e);

            Graphics g = e.Graphics;
            g.SmoothingMode = SmoothingMode.AntiAlias;

            // Background arredondado
            using (GraphicsPath path = GetRoundedRectangle(ClientRectangle, _borderRadius))
            {
                using (SolidBrush bgBrush = new SolidBrush(BackColor))
                {
                    g.FillPath(bgBrush, path);
                }

                // Borda
                Color currentBorderColor = _isFocused ? _focusBorderColor : _borderColor;
                using (Pen borderPen = new Pen(currentBorderColor, _isFocused ? 2 : 1))
                {
                    Rectangle rect = ClientRectangle;
                    rect.Width -= 1;
                    rect.Height -= 1;
                    using (GraphicsPath borderPath = GetRoundedRectangle(rect, _borderRadius))
                    {
                        g.DrawPath(borderPen, borderPath);
                    }
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
