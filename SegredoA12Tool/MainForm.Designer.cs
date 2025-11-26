namespace SegredoA12Tool
{
    partial class MainForm
    {
        private System.ComponentModel.IContainer components = null;

        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        private void InitializeComponent()
        {
            this.components = new System.ComponentModel.Container();
            
            // Colors - Dark Modern Theme
            System.Drawing.Color colorBackground = System.Drawing.ColorTranslator.FromHtml("#2B2B2B");      // Dark gray
            System.Drawing.Color colorPanel = System.Drawing.ColorTranslator.FromHtml("#3C3C3C");          // Medium dark
            System.Drawing.Color colorAccent = System.Drawing.ColorTranslator.FromHtml("#00D9A3");         // Green accent (like image)
            System.Drawing.Color colorText = System.Drawing.ColorTranslator.FromHtml("#FFFFFF");           // White text
            System.Drawing.Color colorTextSecondary = System.Drawing.ColorTranslator.FromHtml("#B0B0B0");  // Gray text
            System.Drawing.Color colorButtonHover = System.Drawing.ColorTranslator.FromHtml("#00F5B8");    // Lighter green
            
            // Components
            this.panelTop = new Guna.UI2.WinForms.Guna2Panel();
            this.labelTitle = new System.Windows.Forms.Label();
            this.btnClose = new Guna.UI2.WinForms.Guna2CircleButton();
            this.btnMinimize = new Guna.UI2.WinForms.Guna2CircleButton();
            
            this.panelDeviceInfo = new Guna.UI2.WinForms.Guna2Panel();
            this.labelDeviceInfoTitle = new System.Windows.Forms.Label();
            
            this.labelModelTitle = new System.Windows.Forms.Label();
            this.labelModelValue = new System.Windows.Forms.Label();
            
            this.labelProductTypeTitle = new System.Windows.Forms.Label();
            this.labelProductTypeValue = new System.Windows.Forms.Label();
            
            this.labelCPIDTitle = new System.Windows.Forms.Label();
            this.labelCPIDValue = new System.Windows.Forms.Label();
            
            this.labelECIDTitle = new System.Windows.Forms.Label();
            this.labelECIDValue = new System.Windows.Forms.Label();
            
            this.labelPWNDTitle = new System.Windows.Forms.Label();
            this.labelPWNDValue = new System.Windows.Forms.Label();
            
            this.pictureBoxLogo = new System.Windows.Forms.PictureBox();
            this.labelSupport = new System.Windows.Forms.Label();
            
            this.panelButtons = new Guna.UI2.WinForms.Guna2Panel();
            this.btnActivate = new Guna.UI2.WinForms.Guna2Button();
            this.btnBlockOTA = new Guna.UI2.WinForms.Guna2Button();
            
            this.progressBar = new Guna.UI2.WinForms.Guna2ProgressBar();
            this.labelProgress = new System.Windows.Forms.Label();
            
            this.txtLog = new System.Windows.Forms.RichTextBox();
            
            this.guna2Elipse = new Guna.UI2.WinForms.Guna2Elipse(this.components);
            this.guna2DragControl = new Guna.UI2.WinForms.Guna2DragControl(this.components);
            
            this.panelTop.SuspendLayout();
            this.panelDeviceInfo.SuspendLayout();
            this.panelButtons.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.pictureBoxLogo)).BeginInit();
            this.SuspendLayout();
            
            // 
            // MainForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 13F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(900, 600);
            this.BackColor = colorBackground;
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.None;
            this.Name = "MainForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterScreen;
            this.Text = "Segredo-A12 Tool";
            this.Load += new System.EventHandler(this.MainForm_Load);
            
            // 
            // guna2Elipse
            // 
            this.guna2Elipse.BorderRadius = 20;
            this.guna2Elipse.TargetControl = this;
            
            // 
            // guna2DragControl
            // 
            this.guna2DragControl.TargetControl = this.panelTop;
            this.guna2DragControl.DockIndicatorTransparencyValue = 0.6;
            
            // 
            // panelTop
            // 
            this.panelTop.BackColor = colorPanel;
            this.panelTop.BorderRadius = 0;
            this.panelTop.Dock = System.Windows.Forms.DockStyle.Top;
            this.panelTop.Location = new System.Drawing.Point(0, 0);
            this.panelTop.Name = "panelTop";
            this.panelTop.Size = new System.Drawing.Size(900, 50);
            this.panelTop.TabIndex = 0;
            this.panelTop.Controls.Add(this.labelTitle);
            this.panelTop.Controls.Add(this.btnClose);
            this.panelTop.Controls.Add(this.btnMinimize);
            
            // 
            // labelTitle
            // 
            this.labelTitle.AutoSize = false;
            this.labelTitle.Font = new System.Drawing.Font("Segoe UI", 14F, System.Drawing.FontStyle.Bold);
            this.labelTitle.ForeColor = colorText;
            this.labelTitle.Location = new System.Drawing.Point(20, 10);
            this.labelTitle.Name = "labelTitle";
            this.labelTitle.Size = new System.Drawing.Size(300, 30);
            this.labelTitle.TabIndex = 0;
            this.labelTitle.Text = "SEGREDO-A12 TOOL";
            this.labelTitle.TextAlign = System.Drawing.ContentAlignment.MiddleLeft;
            
            // 
            // btnClose
            // 
            this.btnClose.Animated = true;
            this.btnClose.BackColor = System.Drawing.Color.Transparent;
            this.btnClose.DisabledState.BorderColor = System.Drawing.Color.DarkGray;
            this.btnClose.DisabledState.CustomBorderColor = System.Drawing.Color.DarkGray;
            this.btnClose.DisabledState.FillColor = System.Drawing.Color.FromArgb(169, 169, 169);
            this.btnClose.DisabledState.ForeColor = System.Drawing.Color.FromArgb(141, 141, 141);
            this.btnClose.FillColor = System.Drawing.ColorTranslator.FromHtml("#E74C3C");
            this.btnClose.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.btnClose.ForeColor = System.Drawing.Color.White;
            this.btnClose.Location = new System.Drawing.Point(850, 10);
            this.btnClose.Name = "btnClose";
            this.btnClose.ShadowDecoration.Mode = Guna.UI2.WinForms.Enums.ShadowMode.Circle;
            this.btnClose.Size = new System.Drawing.Size(30, 30);
            this.btnClose.TabIndex = 1;
            this.btnClose.Click += new System.EventHandler(this.btnClose_Click);
            
            // 
            // btnMinimize
            // 
            this.btnMinimize.Animated = true;
            this.btnMinimize.BackColor = System.Drawing.Color.Transparent;
            this.btnMinimize.DisabledState.BorderColor = System.Drawing.Color.DarkGray;
            this.btnMinimize.DisabledState.CustomBorderColor = System.Drawing.Color.DarkGray;
            this.btnMinimize.DisabledState.FillColor = System.Drawing.Color.FromArgb(169, 169, 169);
            this.btnMinimize.DisabledState.ForeColor = System.Drawing.Color.FromArgb(141, 141, 141);
            this.btnMinimize.FillColor = System.Drawing.ColorTranslator.FromHtml("#FFD26D");
            this.btnMinimize.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.btnMinimize.ForeColor = System.Drawing.Color.White;
            this.btnMinimize.Location = new System.Drawing.Point(810, 10);
            this.btnMinimize.Name = "btnMinimize";
            this.btnMinimize.ShadowDecoration.Mode = Guna.UI2.WinForms.Enums.ShadowMode.Circle;
            this.btnMinimize.Size = new System.Drawing.Size(30, 30);
            this.btnMinimize.TabIndex = 2;
            this.btnMinimize.Click += new System.EventHandler(this.btnMinimize_Click);
            
            // 
            // panelDeviceInfo
            // 
            this.panelDeviceInfo.BackColor = colorPanel;
            this.panelDeviceInfo.BorderRadius = 15;
            this.panelDeviceInfo.Location = new System.Drawing.Point(20, 70);
            this.panelDeviceInfo.Name = "panelDeviceInfo";
            this.panelDeviceInfo.Size = new System.Drawing.Size(500, 250);
            this.panelDeviceInfo.TabIndex = 1;
            this.panelDeviceInfo.Controls.Add(this.labelDeviceInfoTitle);
            this.panelDeviceInfo.Controls.Add(this.labelModelTitle);
            this.panelDeviceInfo.Controls.Add(this.labelModelValue);
            this.panelDeviceInfo.Controls.Add(this.labelProductTypeTitle);
            this.panelDeviceInfo.Controls.Add(this.labelProductTypeValue);
            this.panelDeviceInfo.Controls.Add(this.labelCPIDTitle);
            this.panelDeviceInfo.Controls.Add(this.labelCPIDValue);
            this.panelDeviceInfo.Controls.Add(this.labelECIDTitle);
            this.panelDeviceInfo.Controls.Add(this.labelECIDValue);
            this.panelDeviceInfo.Controls.Add(this.labelPWNDTitle);
            this.panelDeviceInfo.Controls.Add(this.labelPWNDValue);
            
            // 
            // labelDeviceInfoTitle
            // 
            this.labelDeviceInfoTitle.AutoSize = false;
            this.labelDeviceInfoTitle.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.labelDeviceInfoTitle.ForeColor = colorText;
            this.labelDeviceInfoTitle.Location = new System.Drawing.Point(15, 10);
            this.labelDeviceInfoTitle.Name = "labelDeviceInfoTitle";
            this.labelDeviceInfoTitle.Size = new System.Drawing.Size(200, 25);
            this.labelDeviceInfoTitle.TabIndex = 0;
            this.labelDeviceInfoTitle.Text = "Device Information";
            
            // 
            // labelModelTitle
            // 
            this.labelModelTitle.AutoSize = true;
            this.labelModelTitle.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.labelModelTitle.ForeColor = colorTextSecondary;
            this.labelModelTitle.Location = new System.Drawing.Point(15, 50);
            this.labelModelTitle.Name = "labelModelTitle";
            this.labelModelTitle.Size = new System.Drawing.Size(80, 15);
            this.labelModelTitle.TabIndex = 1;
            this.labelModelTitle.Text = "Model name:";
            
            // 
            // labelModelValue
            // 
            this.labelModelValue.AutoSize = true;
            this.labelModelValue.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.labelModelValue.ForeColor = colorAccent;
            this.labelModelValue.Location = new System.Drawing.Point(120, 50);
            this.labelModelValue.Name = "labelModelValue";
            this.labelModelValue.Size = new System.Drawing.Size(10, 15);
            this.labelModelValue.TabIndex = 2;
            this.labelModelValue.Text = "-";
            
            // 
            // labelProductTypeTitle
            // 
            this.labelProductTypeTitle.AutoSize = true;
            this.labelProductTypeTitle.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.labelProductTypeTitle.ForeColor = colorTextSecondary;
            this.labelProductTypeTitle.Location = new System.Drawing.Point(15, 80);
            this.labelProductTypeTitle.Name = "labelProductTypeTitle";
            this.labelProductTypeTitle.Size = new System.Drawing.Size(80, 15);
            this.labelProductTypeTitle.TabIndex = 3;
            this.labelProductTypeTitle.Text = "Product type:";
            
            // 
            // labelProductTypeValue
            // 
            this.labelProductTypeValue.AutoSize = true;
            this.labelProductTypeValue.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.labelProductTypeValue.ForeColor = colorAccent;
            this.labelProductTypeValue.Location = new System.Drawing.Point(120, 80);
            this.labelProductTypeValue.Name = "labelProductTypeValue";
            this.labelProductTypeValue.Size = new System.Drawing.Size(10, 15);
            this.labelProductTypeValue.TabIndex = 4;
            this.labelProductTypeValue.Text = "-";
            
            // 
            // labelCPIDTitle
            // 
            this.labelCPIDTitle.AutoSize = true;
            this.labelCPIDTitle.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.labelCPIDTitle.ForeColor = colorTextSecondary;
            this.labelCPIDTitle.Location = new System.Drawing.Point(15, 110);
            this.labelCPIDTitle.Name = "labelCPIDTitle";
            this.labelCPIDTitle.Size = new System.Drawing.Size(35, 15);
            this.labelCPIDTitle.TabIndex = 5;
            this.labelCPIDTitle.Text = "CPID:";
            
            // 
            // labelCPIDValue
            // 
            this.labelCPIDValue.AutoSize = true;
            this.labelCPIDValue.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.labelCPIDValue.ForeColor = colorAccent;
            this.labelCPIDValue.Location = new System.Drawing.Point(120, 110);
            this.labelCPIDValue.Name = "labelCPIDValue";
            this.labelCPIDValue.Size = new System.Drawing.Size(10, 15);
            this.labelCPIDValue.TabIndex = 6;
            this.labelCPIDValue.Text = "-";
            
            // 
            // labelECIDTitle
            // 
            this.labelECIDTitle.AutoSize = true;
            this.labelECIDTitle.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.labelECIDTitle.ForeColor = colorTextSecondary;
            this.labelECIDTitle.Location = new System.Drawing.Point(15, 140);
            this.labelECIDTitle.Name = "labelECIDTitle";
            this.labelECIDTitle.Size = new System.Drawing.Size(35, 15);
            this.labelECIDTitle.TabIndex = 7;
            this.labelECIDTitle.Text = "ECID:";
            
            // 
            // labelECIDValue
            // 
            this.labelECIDValue.AutoSize = true;
            this.labelECIDValue.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.labelECIDValue.ForeColor = colorAccent;
            this.labelECIDValue.Location = new System.Drawing.Point(120, 140);
            this.labelECIDValue.Name = "labelECIDValue";
            this.labelECIDValue.Size = new System.Drawing.Size(10, 15);
            this.labelECIDValue.TabIndex = 8;
            this.labelECIDValue.Text = "-";
            
            // 
            // labelPWNDTitle
            // 
            this.labelPWNDTitle.AutoSize = true;
            this.labelPWNDTitle.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.labelPWNDTitle.ForeColor = colorTextSecondary;
            this.labelPWNDTitle.Location = new System.Drawing.Point(15, 170);
            this.labelPWNDTitle.Name = "labelPWNDTitle";
            this.labelPWNDTitle.Size = new System.Drawing.Size(45, 15);
            this.labelPWNDTitle.TabIndex = 9;
            this.labelPWNDTitle.Text = "PWND:";
            
            // 
            // labelPWNDValue
            // 
            this.labelPWNDValue.AutoSize = true;
            this.labelPWNDValue.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.labelPWNDValue.ForeColor = colorAccent;
            this.labelPWNDValue.Location = new System.Drawing.Point(120, 170);
            this.labelPWNDValue.Name = "labelPWNDValue";
            this.labelPWNDValue.Size = new System.Drawing.Size(10, 15);
            this.labelPWNDValue.TabIndex = 10;
            this.labelPWNDValue.Text = "-";
            
            // 
            // pictureBoxLogo (placeholder - verde como na imagem)
            // 
            this.pictureBoxLogo.BackColor = colorPanel;
            this.pictureBoxLogo.BorderStyle = System.Windows.Forms.BorderStyle.None;
            this.pictureBoxLogo.Location = new System.Drawing.Point(540, 70);
            this.pictureBoxLogo.Name = "pictureBoxLogo";
            this.pictureBoxLogo.Size = new System.Drawing.Size(340, 200);
            this.pictureBoxLogo.SizeMode = System.Windows.Forms.PictureBoxSizeMode.CenterImage;
            this.pictureBoxLogo.TabIndex = 2;
            this.pictureBoxLogo.TabStop = false;
            
            // 
            // labelSupport
            // 
            this.labelSupport.AutoSize = true;
            this.labelSupport.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.labelSupport.ForeColor = colorTextSecondary;
            this.labelSupport.Location = new System.Drawing.Point(790, 280);
            this.labelSupport.Name = "labelSupport";
            this.labelSupport.Size = new System.Drawing.Size(90, 15);
            this.labelSupport.TabIndex = 3;
            this.labelSupport.Text = "SUPORTE";
            this.labelSupport.Cursor = System.Windows.Forms.Cursors.Hand;
            
            // 
            // panelButtons
            // 
            this.panelButtons.BackColor = System.Drawing.Color.Transparent;
            this.panelButtons.Location = new System.Drawing.Point(20, 340);
            this.panelButtons.Name = "panelButtons";
            this.panelButtons.Size = new System.Drawing.Size(860, 80);
            this.panelButtons.TabIndex = 4;
            this.panelButtons.Controls.Add(this.btnActivate);
            this.panelButtons.Controls.Add(this.btnBlockOTA);
            
            // 
            // btnActivate
            // 
            this.btnActivate.Animated = true;
            this.btnActivate.BorderRadius = 10;
            this.btnActivate.DisabledState.BorderColor = System.Drawing.Color.DarkGray;
            this.btnActivate.DisabledState.CustomBorderColor = System.Drawing.Color.DarkGray;
            this.btnActivate.DisabledState.FillColor = System.Drawing.Color.FromArgb(169, 169, 169);
            this.btnActivate.DisabledState.ForeColor = System.Drawing.Color.FromArgb(141, 141, 141);
            this.btnActivate.FillColor = colorAccent;
            this.btnActivate.Font = new System.Drawing.Font("Segoe UI", 12F, System.Drawing.FontStyle.Bold);
            this.btnActivate.ForeColor = System.Drawing.Color.White;
            this.btnActivate.HoverState.FillColor = colorButtonHover;
            this.btnActivate.Location = new System.Drawing.Point(0, 10);
            this.btnActivate.Name = "btnActivate";
            this.btnActivate.ShadowDecoration.BorderRadius = 10;
            this.btnActivate.ShadowDecoration.Depth = 15;
            this.btnActivate.ShadowDecoration.Enabled = true;
            this.btnActivate.Size = new System.Drawing.Size(400, 60);
            this.btnActivate.TabIndex = 0;
            this.btnActivate.Text = "Activate / Jailbreak";
            this.btnActivate.Click += new System.EventHandler(this.btnActivate_Click);
            
            // 
            // btnBlockOTA
            // 
            this.btnBlockOTA.Animated = true;
            this.btnBlockOTA.BorderRadius = 10;
            this.btnBlockOTA.DisabledState.BorderColor = System.Drawing.Color.DarkGray;
            this.btnBlockOTA.DisabledState.CustomBorderColor = System.Drawing.Color.DarkGray;
            this.btnBlockOTA.DisabledState.FillColor = System.Drawing.Color.FromArgb(169, 169, 169);
            this.btnBlockOTA.DisabledState.ForeColor = System.Drawing.Color.FromArgb(141, 141, 141);
            this.btnBlockOTA.FillColor = System.Drawing.ColorTranslator.FromHtml("#6C757D");
            this.btnBlockOTA.Font = new System.Drawing.Font("Segoe UI", 12F, System.Drawing.FontStyle.Bold);
            this.btnBlockOTA.ForeColor = System.Drawing.Color.White;
            this.btnBlockOTA.HoverState.FillColor = System.Drawing.ColorTranslator.FromHtml("#5A6268");
            this.btnBlockOTA.Location = new System.Drawing.Point(420, 10);
            this.btnBlockOTA.Name = "btnBlockOTA";
            this.btnBlockOTA.ShadowDecoration.BorderRadius = 10;
            this.btnBlockOTA.ShadowDecoration.Depth = 15;
            this.btnBlockOTA.ShadowDecoration.Enabled = true;
            this.btnBlockOTA.Size = new System.Drawing.Size(440, 60);
            this.btnBlockOTA.TabIndex = 1;
            this.btnBlockOTA.Text = "Block OTA / Disable Passcode";
            this.btnBlockOTA.Click += new System.EventHandler(this.btnBlockOTA_Click);
            
            // 
            // progressBar
            // 
            this.progressBar.BorderRadius = 5;
            this.progressBar.FillColor = colorPanel;
            this.progressBar.Location = new System.Drawing.Point(20, 440);
            this.progressBar.Name = "progressBar";
            this.progressBar.ProgressColor = colorAccent;
            this.progressBar.ProgressColor2 = colorButtonHover;
            this.progressBar.ShadowDecoration.BorderRadius = 5;
            this.progressBar.ShadowDecoration.Depth = 10;
            this.progressBar.ShadowDecoration.Enabled = true;
            this.progressBar.Size = new System.Drawing.Size(860, 15);
            this.progressBar.TabIndex = 5;
            this.progressBar.TextRenderingHint = System.Drawing.Text.TextRenderingHint.SystemDefault;
            
            // 
            // labelProgress
            // 
            this.labelProgress.AutoSize = true;
            this.labelProgress.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.labelProgress.ForeColor = colorTextSecondary;
            this.labelProgress.Location = new System.Drawing.Point(20, 460);
            this.labelProgress.Name = "labelProgress";
            this.labelProgress.Size = new System.Drawing.Size(80, 15);
            this.labelProgress.TabIndex = 6;
            this.labelProgress.Text = "Ready...";
            
            // 
            // txtLog
            // 
            this.txtLog.BackColor = colorPanel;
            this.txtLog.BorderStyle = System.Windows.Forms.BorderStyle.None;
            this.txtLog.Font = new System.Drawing.Font("Consolas", 9F);
            this.txtLog.ForeColor = colorAccent;
            this.txtLog.Location = new System.Drawing.Point(20, 490);
            this.txtLog.Name = "txtLog";
            this.txtLog.ReadOnly = true;
            this.txtLog.Size = new System.Drawing.Size(860, 90);
            this.txtLog.TabIndex = 7;
            this.txtLog.Text = "═══ Segredo-A12 Tool v3.0 ═══\\nReady to connect device...";
            
            // Add all controls to form
            this.Controls.Add(this.panelTop);
            this.Controls.Add(this.panelDeviceInfo);
            this.Controls.Add(this.pictureBoxLogo);
            this.Controls.Add(this.labelSupport);
            this.Controls.Add(this.panelButtons);
            this.Controls.Add(this.progressBar);
            this.Controls.Add(this.labelProgress);
            this.Controls.Add(this.txtLog);
            
            this.panelTop.ResumeLayout(false);
            this.panelDeviceInfo.ResumeLayout(false);
            this.panelDeviceInfo.PerformLayout();
            this.panelButtons.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)(this.pictureBoxLogo)).EndInit();
            this.ResumeLayout(false);
            this.PerformLayout();
        }

        #region Components
        private Guna.UI2.WinForms.Guna2Panel panelTop;
        private System.Windows.Forms.Label labelTitle;
        private Guna.UI2.WinForms.Guna2CircleButton btnClose;
        private Guna.UI2.WinForms.Guna2CircleButton btnMinimize;
        
        private Guna.UI2.WinForms.Guna2Panel panelDeviceInfo;
        private System.Windows.Forms.Label labelDeviceInfoTitle;
        private System.Windows.Forms.Label labelModelTitle;
        private System.Windows.Forms.Label labelModelValue;
        private System.Windows.Forms.Label labelProductTypeTitle;
        private System.Windows.Forms.Label labelProductTypeValue;
        private System.Windows.Forms.Label labelCPIDTitle;
        private System.Windows.Forms.Label labelCPIDValue;
        private System.Windows.Forms.Label labelECIDTitle;
        private System.Windows.Forms.Label labelECIDValue;
        private System.Windows.Forms.Label labelPWNDTitle;
        private System.Windows.Forms.Label labelPWNDValue;
        
        private System.Windows.Forms.PictureBox pictureBoxLogo;
        private System.Windows.Forms.Label labelSupport;
        
        private Guna.UI2.WinForms.Guna2Panel panelButtons;
        private Guna.UI2.WinForms.Guna2Button btnActivate;
        private Guna.UI2.WinForms.Guna2Button btnBlockOTA;
        
        private Guna.UI2.WinForms.Guna2ProgressBar progressBar;
        private System.Windows.Forms.Label labelProgress;
        private System.Windows.Forms.RichTextBox txtLog;
        
        private Guna.UI2.WinForms.Guna2Elipse guna2Elipse;
        private Guna.UI2.WinForms.Guna2DragControl guna2DragControl;
        #endregion
    }
}
