using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace iSkorpionA12
{
    public partial class CustomMessageBox : Form
    {
        private static DialogResult _result;

        public CustomMessageBox()
        {
            InitializeComponent();
            this.Paint += CustomMessageBox_Paint; // Add paint event for rounded corners
        }

        public static DialogResult Show(string message)
        {
            return Show(message, "Message", MessageBoxButtons.OK, MessageBoxIcon.None);
        }

        public static DialogResult Show(string message, string title)
        {
            return Show(message, title, MessageBoxButtons.OK, MessageBoxIcon.None);
        }

        public static DialogResult Show(string message, string title, MessageBoxButtons buttons)
        {
            return Show(message, title, buttons, MessageBoxIcon.None);
        }

        public static DialogResult Show(string message, string title, MessageBoxButtons buttons, MessageBoxIcon icon)
        {
            using (var form = new CustomMessageBox())
            {
                form.SetupDialog(message, title, buttons, icon);
                form.ShowDialog();
                return _result;
            }
        }

        private void SetupDialog(string message, string title, MessageBoxButtons buttons, MessageBoxIcon icon)
        {
            // Set basic properties
            this.Text = title;
            lblTitle.Text = title;
            lblMessage.Text = message;

            // Set icon
            SetIcon(icon);

            // Create buttons based on MessageBoxButtons
            CreateButtons(buttons);

            // Size the form appropriately
            SizeFormToContent();
        }

        private void SetIcon(MessageBoxIcon icon)
        {
            switch (icon)
            {
                case MessageBoxIcon.Information:
                    picIcon.Image = SystemIcons.Information.ToBitmap();
                    picIcon.Visible = true;
                    break;
                case MessageBoxIcon.Warning:
                    picIcon.Image = SystemIcons.Warning.ToBitmap();
                    picIcon.Visible = true;
                    break;
                case MessageBoxIcon.Error:
                    picIcon.Image = SystemIcons.Error.ToBitmap();
                    picIcon.Visible = true;
                    break;
                case MessageBoxIcon.Question:
                    picIcon.Image = SystemIcons.Question.ToBitmap();
                    picIcon.Visible = true;
                    break;
                default:
                    picIcon.Visible = false;
                    lblMessage.Location = new Point(20, lblMessage.Location.Y);
                    break;
            }
        }

        private void CreateButtons(MessageBoxButtons buttons)
        {
            // First, hide all buttons
            btnOK.Visible = false;
            btnCancel.Visible = false;
            btnYes.Visible = false;
            btnNo.Visible = false;
            btnRetry.Visible = false;
            btnAbort.Visible = false;
            btnIgnore.Visible = false;

            List<Button> buttonList = new List<Button>();

            switch (buttons)
            {
                case MessageBoxButtons.OK:
                    buttonList.Add(btnOK);
                    break;
                case MessageBoxButtons.OKCancel:
                    buttonList.Add(btnOK);
                    buttonList.Add(btnCancel);
                    break;
                case MessageBoxButtons.YesNo:
                    buttonList.Add(btnYes);
                    buttonList.Add(btnNo);
                    break;
                case MessageBoxButtons.YesNoCancel:
                    buttonList.Add(btnYes);
                    buttonList.Add(btnNo);
                    buttonList.Add(btnCancel);
                    break;
                case MessageBoxButtons.RetryCancel:
                    buttonList.Add(btnRetry);
                    buttonList.Add(btnCancel);
                    break;
                case MessageBoxButtons.AbortRetryIgnore:
                    buttonList.Add(btnAbort);
                    buttonList.Add(btnRetry);
                    buttonList.Add(btnIgnore);
                    break;
                default:
                    buttonList.Add(btnOK);
                    break;
            }

            // Show only the required buttons
            foreach (Button btn in buttonList)
            {
                btn.Visible = true;
            }

            // Position buttons
            PositionButtons(buttonList.ToArray());
        }

        private void PositionButtons(Button[] buttons)
        {
            int rightMargin = 20;
            int buttonSpacing = 10;
            int totalWidth = 0;

            // Calculate total width needed for buttons
            foreach (Button btn in buttons)
            {
                totalWidth += btn.Width + buttonSpacing;
            }
            totalWidth -= buttonSpacing; // Remove last spacing

            // Start position (right-aligned)
            int startX = this.ClientSize.Width - totalWidth - rightMargin;
            int y = panelButtons.Height / 2 - btnOK.Height / 2; // Center vertically in panel

            // Position each button
            foreach (Button btn in buttons)
            {
                btn.Location = new Point(startX, y);
                startX += btn.Width + buttonSpacing;
            }
        }

        private void SizeFormToContent()
        {
            // Calculate required height based on message text
            using (Graphics g = CreateGraphics())
            {
                SizeF textSize = g.MeasureString(lblMessage.Text, lblMessage.Font, lblMessage.Width);
                int requiredHeight = (int)textSize.Height + 120; // Add space for title, icon, buttons, and margins

                if (requiredHeight > this.ClientSize.Height - panelButtons.Height)
                {
                    this.Height = Math.Min(requiredHeight + panelButtons.Height, 600);
                }
            }

            // Center the form on screen after sizing
            this.CenterToScreen();
        }

        // Rounded corners implementation
        private void CustomMessageBox_Paint(object sender, PaintEventArgs e)
        {
            GraphicsPath path = new GraphicsPath();
            int radius = 20; // Corner radius
            Rectangle rect = this.ClientRectangle;

            // Create rounded rectangle path
            path.AddArc(rect.X, rect.Y, radius, radius, 180, 90);
            path.AddArc(rect.Right - radius, rect.Y, radius, radius, 270, 90);
            path.AddArc(rect.Right - radius, rect.Bottom - radius, radius, radius, 0, 90);
            path.AddArc(rect.X, rect.Bottom - radius, radius, radius, 90, 90);
            path.CloseFigure();

            this.Region = new Region(path);
        }

        // Button click handlers
        private void btnOK_Click(object sender, EventArgs e)
        {
            _result = DialogResult.OK;
            this.Close();
        }

        private void btnCancel_Click(object sender, EventArgs e)
        {
            _result = DialogResult.Cancel;
            this.Close();
        }

        private void btnYes_Click(object sender, EventArgs e)
        {
            _result = DialogResult.Yes;
            this.Close();
        }

        private void btnNo_Click(object sender, EventArgs e)
        {
            _result = DialogResult.No;
            this.Close();
        }

        private void btnRetry_Click(object sender, EventArgs e)
        {
            _result = DialogResult.Retry;
            this.Close();
        }

        private void btnAbort_Click(object sender, EventArgs e)
        {
            _result = DialogResult.Abort;
            this.Close();
        }

        private void btnIgnore_Click(object sender, EventArgs e)
        {
            _result = DialogResult.Ignore;
            this.Close();
        }

        // Allow dragging the form by clicking on the title or anywhere on the form
        private void lblTitle_MouseDown(object sender, MouseEventArgs e)
        {
            if (e.Button == MouseButtons.Left)
            {
                NativeMethods.ReleaseCapture();
                NativeMethods.SendMessage(Handle, NativeMethods.WM_NCLBUTTONDOWN, NativeMethods.HT_CAPTION, 0);
            }
        }

        private void CustomMessageBox_MouseDown(object sender, MouseEventArgs e)
        {
            if (e.Button == MouseButtons.Left)
            {
                NativeMethods.ReleaseCapture();
                NativeMethods.SendMessage(Handle, NativeMethods.WM_NCLBUTTONDOWN, NativeMethods.HT_CAPTION, 0);
            }
        }

        // Close button click
        private void btnClose_Click(object sender, EventArgs e)
        {
            _result = DialogResult.Cancel;
            this.Close();
        }
    }

    // Native methods for form dragging
    internal static class NativeMethods
    {
        public const int WM_NCLBUTTONDOWN = 0xA1;
        public const int HT_CAPTION = 0x2;

        [System.Runtime.InteropServices.DllImport("user32.dll")]
        public static extern int SendMessage(IntPtr hWnd, int Msg, int wParam, int lParam);

        [System.Runtime.InteropServices.DllImport("user32.dll")]
        public static extern bool ReleaseCapture();
    }
}