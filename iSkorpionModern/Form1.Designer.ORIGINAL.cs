// Form1.Designer.cs - Estilo: Segredo Bypass Premium (branco)
namespace iSkorpionA12
{
    partial class Form1
    {
        /// <summary>
        /// Variable del diseñador necesaria.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Limpiar los recursos que se estén usando.
        /// </summary>
        /// <param name="disposing">true si los recursos administrados se deben desechar; false en caso contrario.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Código generado por el Diseñador de Windows Forms

        /// <summary>
        /// Método necesario para admitir el Diseñador. No se puede modificar
        /// el contenido de este método con el editor de código.
        /// </summary>
        private void InitializeComponent()
        {
            this.components = new System.ComponentModel.Container();
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(Form1));

            // Colors (ULTRA MODERN CYBERPUNK NEON PALETTE)
            var colorBackground = System.Drawing.ColorTranslator.FromHtml("#0A0E27"); // Deep space dark
            var colorCard = System.Drawing.ColorTranslator.FromHtml("#141B2D");       // Ultra dark cards
            var colorPrimaryFrom = System.Drawing.ColorTranslator.FromHtml("#00FFFF"); // Pure Cyan NEON
            var colorPrimaryTo = System.Drawing.ColorTranslator.FromHtml("#FF00FF");   // Magenta EXPLOSIVE
            var colorAccent = System.Drawing.ColorTranslator.FromHtml("#FF0090");     // destaque / progress
            var colorTextSecondary = System.Drawing.ColorTranslator.FromHtml("#E0E0FF"); // labels cinza
            var colorDisabled = System.Drawing.ColorTranslator.FromHtml("#1F1F3E");   // botão desabilitado / cinza
            var colorBorder = System.Drawing.ColorTranslator.FromHtml("#2A2F5F");

            // Labels & basic info
            this.labelType = new System.Windows.Forms.Label();
            this.labelVersion = new System.Windows.Forms.Label();
            this.labelSN = new System.Windows.Forms.Label();
            this.ModeloffHello = new System.Windows.Forms.Label();
            this.label15 = new System.Windows.Forms.Label();
            this.label16 = new System.Windows.Forms.Label();
            this.label20 = new System.Windows.Forms.Label();
            this.label23 = new System.Windows.Forms.Label();

            // Guna controls
            this.guna2CircleButton2 = new Guna.UI2.WinForms.Guna2CircleButton();
            this.guna2CircleButton1 = new Guna.UI2.WinForms.Guna2CircleButton();
            this.guna2Elipse1 = new Guna.UI2.WinForms.Guna2Elipse(this.components);
            this.guna2GradientButton3 = new Guna.UI2.WinForms.Guna2GradientButton();
            this.guna2GradientButton2 = new Guna.UI2.WinForms.Guna2GradientButton();
            this.guna2GradientButton1 = new Guna.UI2.WinForms.Guna2GradientButton();
            this.Guna2ProgressBar1 = new Guna.UI2.WinForms.Guna2ProgressBar();
            this.ActivateButton = new Guna.UI2.WinForms.Guna2GradientButton();
            this.LogsBox = new Guna.UI2.WinForms.Guna2TextBox();

            // Other controls
            this.label1 = new System.Windows.Forms.Label();
            this.Status = new System.Windows.Forms.Label();
            this.labelActivaction = new System.Windows.Forms.Label();
            this.pictureBox3 = new System.Windows.Forms.PictureBox();
            this.pictureBoxModel = new System.Windows.Forms.PictureBox();
            this.pictureBoxDC = new System.Windows.Forms.PictureBox();
            this.labelInfoProgres = new System.Windows.Forms.Label();
            this.label2 = new System.Windows.Forms.Label();
            this.label3 = new System.Windows.Forms.Label();
            this.label4 = new System.Windows.Forms.Label();
            this.labelIMEI = new System.Windows.Forms.Label();
            this.label5 = new System.Windows.Forms.Label();
            this.labelECID = new System.Windows.Forms.Label();

            ((System.ComponentModel.ISupportInitialize)(this.pictureBox3)).BeginInit();
            ((System.ComponentModel.ISupportInitialize)(this.pictureBoxModel)).BeginInit();
            ((System.ComponentModel.ISupportInitialize)(this.pictureBoxDC)).BeginInit();
            this.SuspendLayout();

            // 
            // Form (core)
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(6F, 13F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.BackColor = colorBackground;
            this.BackgroundImage = ((System.Drawing.Image)(resources.GetObject("$this.BackgroundImage")));
            this.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch;
            this.ClientSize = new System.Drawing.Size(1000, 600);

            // 
            // labelType
            // 
            this.labelType.AutoSize = true;
            this.labelType.BackColor = System.Drawing.Color.Transparent;
            this.labelType.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.labelType.ForeColor = System.Drawing.Color.FromArgb(51, 51, 51);
            this.labelType.Location = new System.Drawing.Point(250, 110);
            this.labelType.Name = "labelType";
            this.labelType.Size = new System.Drawing.Size(29, 15);
            this.labelType.TabIndex = 10;
            this.labelType.Text = "N/A";
            // 
            // labelVersion
            // 
            this.labelVersion.AutoSize = true;
            this.labelVersion.BackColor = System.Drawing.Color.Transparent;
            this.labelVersion.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.labelVersion.ForeColor = System.Drawing.Color.FromArgb(51, 51, 51);
            this.labelVersion.Location = new System.Drawing.Point(650, 110);
            this.labelVersion.Name = "labelVersion";
            this.labelVersion.Size = new System.Drawing.Size(29, 15);
            this.labelVersion.TabIndex = 12;
            this.labelVersion.Text = "N/A";
            // 
            // labelSN
            // 
            this.labelSN.AutoSize = true;
            this.labelSN.BackColor = System.Drawing.Color.Transparent;
            this.labelSN.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.labelSN.ForeColor = System.Drawing.ColorTranslator.FromHtml("#FF00FF");
            this.labelSN.Location = new System.Drawing.Point(450, 110);
            this.labelSN.Name = "labelSN";
            this.labelSN.Size = new System.Drawing.Size(29, 15);
            this.labelSN.TabIndex = 11;
            this.labelSN.Text = "N/A";
            // 
            // ModeloffHello
            // 
            this.ModeloffHello.AutoSize = true;
            this.ModeloffHello.BackColor = System.Drawing.Color.Transparent;
            this.ModeloffHello.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.ModeloffHello.ForeColor = System.Drawing.Color.FromArgb(51, 51, 51);
            this.ModeloffHello.Location = new System.Drawing.Point(50, 110);
            this.ModeloffHello.Name = "ModeloffHello";
            this.ModeloffHello.Size = new System.Drawing.Size(29, 15);
            this.ModeloffHello.TabIndex = 9;
            this.ModeloffHello.Text = "N/A";
            // 
            // label15 (iOS)
            // 
            this.label15.AutoSize = true;
            this.label15.BackColor = System.Drawing.Color.Transparent;
            this.label15.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.label15.ForeColor = colorTextSecondary;
            this.label15.Location = new System.Drawing.Point(650, 80);
            this.label15.Name = "label15";
            this.label15.Size = new System.Drawing.Size(26, 15);
            this.label15.TabIndex = 5;
            this.label15.Text = "iOS";
            // 
            // label16 (ProductType)
            // 
            this.label16.AutoSize = true;
            this.label16.BackColor = System.Drawing.Color.Transparent;
            this.label16.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.label16.ForeColor = colorTextSecondary;
            this.label16.Location = new System.Drawing.Point(250, 80);
            this.label16.Name = "label16";
            this.label16.Size = new System.Drawing.Size(80, 15);
            this.label16.TabIndex = 4;
            this.label16.Text = "ProductType ";
            // 
            // label20 (Serial)
            // 
            this.label20.AutoSize = true;
            this.label20.BackColor = System.Drawing.Color.Transparent;
            this.label20.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.label20.ForeColor = colorTextSecondary;
            this.label20.Location = new System.Drawing.Point(450, 80);
            this.label20.Name = "label20";
            this.label20.Size = new System.Drawing.Size(38, 15);
            this.label20.TabIndex = 6;
            this.label20.Text = "Serial";
            // 
            // label23 (Model)
            // 
            this.label23.AutoSize = true;
            this.label23.BackColor = System.Drawing.Color.Transparent;
            this.label23.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.label23.ForeColor = colorTextSecondary;
            this.label23.Location = new System.Drawing.Point(50, 80);
            this.label23.Name = "label23";
            this.label23.Size = new System.Drawing.Size(42, 15);
            this.label23.TabIndex = 3;
            this.label23.Text = "Model";
            // 
            // guna2CircleButton2 (Yellow small indicator)
            // 
            this.guna2CircleButton2.BackColor = System.Drawing.Color.Transparent;
            this.guna2CircleButton2.Cursor = System.Windows.Forms.Cursors.Hand;
            this.guna2CircleButton2.DisabledState.BorderColor = System.Drawing.Color.DarkGray;
            this.guna2CircleButton2.DisabledState.CustomBorderColor = System.Drawing.Color.DarkGray;
            this.guna2CircleButton2.DisabledState.FillColor = System.Drawing.Color.FromArgb(169, 169, 169);
            this.guna2CircleButton2.DisabledState.ForeColor = System.Drawing.Color.FromArgb(141, 141, 141);
            this.guna2CircleButton2.FillColor = System.Drawing.ColorTranslator.FromHtml("#FFD26D"); // soft yellow
            this.guna2CircleButton2.Font = new System.Drawing.Font("Segoe UI", 11F);
            this.guna2CircleButton2.ForeColor = System.Drawing.Color.Transparent;
            this.guna2CircleButton2.Location = new System.Drawing.Point(31, 6);
            this.guna2CircleButton2.Margin = new System.Windows.Forms.Padding(2);
            this.guna2CircleButton2.Name = "guna2CircleButton2";
            this.guna2CircleButton2.ShadowDecoration.Depth = 25;
            this.guna2CircleButton2.ShadowDecoration.Enabled = true;
            this.guna2CircleButton2.ShadowDecoration.Mode = Guna.UI2.WinForms.Enums.ShadowMode.Circle;
            this.guna2CircleButton2.Size = new System.Drawing.Size(13, 14);
            this.guna2CircleButton2.TabIndex = 1;
            this.guna2CircleButton2.Text = "";
            this.guna2CircleButton2.Click += new System.EventHandler(this.guna2CircleButton2_Click);
            // 
            // guna2CircleButton1 (Red small indicator)
            // 
            this.guna2CircleButton1.BackColor = System.Drawing.Color.Transparent;
            this.guna2CircleButton1.Cursor = System.Windows.Forms.Cursors.Hand;
            this.guna2CircleButton1.DisabledState.BorderColor = System.Drawing.Color.DarkGray;
            this.guna2CircleButton1.DisabledState.CustomBorderColor = System.Drawing.Color.DarkGray;
            this.guna2CircleButton1.DisabledState.FillColor = System.Drawing.Color.FromArgb(169, 169, 169);
            this.guna2CircleButton1.DisabledState.ForeColor = System.Drawing.Color.FromArgb(141, 141, 141);
            this.guna2CircleButton1.FillColor = System.Drawing.ColorTranslator.FromHtml("#E74C3C"); // soft red
            this.guna2CircleButton1.Font = new System.Drawing.Font("Segoe UI", 11F);
            this.guna2CircleButton1.ForeColor = System.Drawing.Color.Transparent;
            this.guna2CircleButton1.Location = new System.Drawing.Point(11, 6);
            this.guna2CircleButton1.Margin = new System.Windows.Forms.Padding(2);
            this.guna2CircleButton1.Name = "guna2CircleButton1";
            this.guna2CircleButton1.ShadowDecoration.Depth = 25;
            this.guna2CircleButton1.ShadowDecoration.Enabled = true;
            this.guna2CircleButton1.ShadowDecoration.Mode = Guna.UI2.WinForms.Enums.ShadowMode.Circle;
            this.guna2CircleButton1.Size = new System.Drawing.Size(13, 14);
            this.guna2CircleButton1.TabIndex = 0;
            this.guna2CircleButton1.Text = "";
            this.guna2CircleButton1.Click += new System.EventHandler(this.guna2CircleButton1_Click);
            // 
            // label1 (top center small)
            // 
            this.label1.AutoSize = true;
            this.label1.BackColor = System.Drawing.Color.Transparent;
            this.label1.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.label1.ForeColor = colorPrimaryFrom;
            this.label1.Location = new System.Drawing.Point(415, 5);
            this.label1.Name = "label1";
            this.label1.Size = new System.Drawing.Size(171, 15);
            this.label1.TabIndex = 20;
            this.label1.Text = "SEGREDO BYPASS PREMIUM";
            this.label1.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            // 
            // guna2Elipse1
            // 
            this.guna2Elipse1.BorderRadius = 30;
            this.guna2Elipse1.TargetControl = this;
            // 
            // guna2GradientButton3 (top-right small)
            // 
            this.guna2GradientButton3.Animated = true;
            this.guna2GradientButton3.BackColor = System.Drawing.Color.Transparent;
            this.guna2GradientButton3.BackgroundImage = ((System.Drawing.Image)(resources.GetObject("guna2GradientButton3.BackgroundImage")));
            this.guna2GradientButton3.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch;
            this.guna2GradientButton3.BorderColor = System.Drawing.Color.Transparent;
            this.guna2GradientButton3.BorderRadius = 22;
            this.guna2GradientButton3.BorderStyle = System.Drawing.Drawing2D.DashStyle.Solid;
            this.guna2GradientButton3.Cursor = System.Windows.Forms.Cursors.Hand;
            this.guna2GradientButton3.DisabledState.BorderColor = System.Drawing.Color.FromArgb(200, 200, 200);
            this.guna2GradientButton3.DisabledState.FillColor = colorDisabled;
            this.guna2GradientButton3.FillColor = System.Drawing.Color.Transparent;
            this.guna2GradientButton3.FillColor2 = System.Drawing.Color.Transparent;
            this.guna2GradientButton3.Font = new System.Drawing.Font("Lucida Sans Unicode", 11F, System.Drawing.FontStyle.Bold);
            this.guna2GradientButton3.ForeColor = System.Drawing.SystemColors.WindowText;
            this.guna2GradientButton3.GradientMode = System.Drawing.Drawing2D.LinearGradientMode.ForwardDiagonal;
            this.guna2GradientButton3.ImageSize = new System.Drawing.Size(20, 20);
            this.guna2GradientButton3.IndicateFocus = true;
            this.guna2GradientButton3.Location = new System.Drawing.Point(813, 1);
            this.guna2GradientButton3.Name = "guna2GradientButton3";
            this.guna2GradientButton3.PressedColor = System.Drawing.Color.Transparent;
            this.guna2GradientButton3.ShadowDecoration.BorderRadius = 4;
            this.guna2GradientButton3.ShadowDecoration.Depth = 28;
            this.guna2GradientButton3.ShadowDecoration.Enabled = true;
            this.guna2GradientButton3.Size = new System.Drawing.Size(28, 26);
            this.guna2GradientButton3.TabIndex = 22;
            this.guna2GradientButton3.UseTransparentBackground = true;
            this.guna2GradientButton3.Click += new System.EventHandler(this.guna2GradientButton3_Click);
            // 
            // Status (label)
            // 
            this.Status.AutoSize = true;
            this.Status.BackColor = System.Drawing.Color.Transparent;
            this.Status.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.Status.ForeColor = colorTextSecondary;
            this.Status.Location = new System.Drawing.Point(50, 120);
            this.Status.Name = "Status";
            this.Status.Size = new System.Drawing.Size(42, 15);
            this.Status.TabIndex = 13;
            this.Status.Text = "Status";
            // 
            // labelActivaction (value)
            // 
            this.labelActivaction.AutoSize = true;
            this.labelActivaction.BackColor = System.Drawing.Color.Transparent;
            this.labelActivaction.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.labelActivaction.ForeColor = System.Drawing.Color.FromArgb(51, 51, 51);
            this.labelActivaction.Location = new System.Drawing.Point(200, 120);
            this.labelActivaction.Name = "labelActivaction";
            this.labelActivaction.Size = new System.Drawing.Size(29, 15);
            this.labelActivaction.TabIndex = 14;
            this.labelActivaction.Text = "N/A";
            // 
            // pictureBox3
            // 
            this.pictureBox3.BackColor = System.Drawing.Color.Transparent;
            this.pictureBox3.Cursor = System.Windows.Forms.Cursors.Hand;
            this.pictureBox3.Image = global::iSkorpionA12.Properties.Resources.iSkorpionxx;
            this.pictureBox3.Location = new System.Drawing.Point(411, 176);
            this.pictureBox3.Name = "pictureBox3";
            this.pictureBox3.Size = new System.Drawing.Size(19, 17);
            this.pictureBox3.SizeMode = System.Windows.Forms.PictureBoxSizeMode.Zoom;
            this.pictureBox3.TabIndex = 21;
            this.pictureBox3.TabStop = false;
            this.pictureBox3.Click += new System.EventHandler(this.pictureBox3_Click);
            // 
            // pictureBoxModel
            // 
            this.pictureBoxModel.BackColor = System.Drawing.Color.Transparent;
            this.pictureBoxModel.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch;
            this.pictureBoxModel.Location = new System.Drawing.Point(50, 35);
            this.pictureBoxModel.Name = "pictureBoxModel";
            this.pictureBoxModel.Size = new System.Drawing.Size(181, 138);
            this.pictureBoxModel.SizeMode = System.Windows.Forms.PictureBoxSizeMode.Zoom;
            this.pictureBoxModel.TabIndex = 2;
            this.pictureBoxModel.TabStop = false;
            // 
            // pictureBoxDC (card background)
            // 
            this.pictureBoxDC.BackColor = System.Drawing.Color.Transparent;
            this.pictureBoxDC.BackgroundImage = ((System.Drawing.Image)(resources.GetObject("pictureBoxDC.BackgroundImage")));
            this.pictureBoxDC.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch;
            this.pictureBoxDC.Location = new System.Drawing.Point(850, 35);
            this.pictureBoxDC.Name = "pictureBoxDC";
            this.pictureBoxDC.Size = new System.Drawing.Size(110, 138);
            this.pictureBoxDC.SizeMode = System.Windows.Forms.PictureBoxSizeMode.Zoom;
            this.pictureBoxDC.TabIndex = 7;
            this.pictureBoxDC.TabStop = false;
            // 
            // labelInfoProgres
            // 
            this.labelInfoProgres.BackColor = System.Drawing.Color.Transparent;
            this.labelInfoProgres.Font = new System.Drawing.Font("Segoe UI", 7F, System.Drawing.FontStyle.Bold);
            this.labelInfoProgres.ForeColor = colorTextSecondary;
            this.labelInfoProgres.Location = new System.Drawing.Point(200, 510);
            this.labelInfoProgres.Name = "labelInfoProgres";
            this.labelInfoProgres.Size = new System.Drawing.Size(600, 20);
            this.labelInfoProgres.TabIndex = 23;
            this.labelInfoProgres.Text = "Ready";
            this.labelInfoProgres.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            // 
            // Guna2ProgressBar1 (styled)
            // 
            this.Guna2ProgressBar1.BackColor = System.Drawing.Color.Transparent;
            this.Guna2ProgressBar1.BorderColor = colorBorder;
            this.Guna2ProgressBar1.BorderRadius = 22;
            this.Guna2ProgressBar1.FillColor = System.Drawing.ColorTranslator.FromHtml("#EDEFF2"); // trail
            this.Guna2ProgressBar1.ForeColor = System.Drawing.Color.Transparent;
            this.Guna2ProgressBar1.Location = new System.Drawing.Point(200, 480);
            this.Guna2ProgressBar1.Minimum = 0;
            this.Guna2ProgressBar1.Name = "Guna2ProgressBar1";
            this.Guna2ProgressBar1.ProgressBrushMode = Guna.UI2.WinForms.Enums.BrushMode.Solid;
            this.Guna2ProgressBar1.ProgressColor = colorAccent;
            this.Guna2ProgressBar1.ProgressColor2 = colorPrimaryFrom;
            this.Guna2ProgressBar1.ShadowDecoration.BorderRadius = 4;
            this.Guna2ProgressBar1.ShadowDecoration.Depth = 28;
            this.Guna2ProgressBar1.ShadowDecoration.Enabled = true;
            this.Guna2ProgressBar1.Size = new System.Drawing.Size(600, 22);
            this.Guna2ProgressBar1.TabIndex = 24;
            this.Guna2ProgressBar1.TextRenderingHint = System.Drawing.Text.TextRenderingHint.SystemDefault;
            this.Guna2ProgressBar1.Value = 0;
            // 
            // ActivateButton (primary)
            // 
            this.ActivateButton.Animated = true;
            this.ActivateButton.BackColor = System.Drawing.Color.Transparent;
            this.ActivateButton.BorderColor = System.Drawing.Color.Transparent;
            this.ActivateButton.BorderRadius = 25;
            this.ActivateButton.BorderStyle = System.Drawing.Drawing2D.DashStyle.Solid;
            this.ActivateButton.Cursor = System.Windows.Forms.Cursors.Hand;
            this.ActivateButton.DisabledState.BorderColor = System.Drawing.Color.FromArgb(200, 200, 200);
            this.ActivateButton.DisabledState.CustomBorderColor = System.Drawing.Color.FromArgb(200, 200, 200);
            this.ActivateButton.DisabledState.FillColor = colorDisabled;
            this.ActivateButton.DisabledState.FillColor2 = colorDisabled;
            this.ActivateButton.DisabledState.ForeColor = System.Drawing.Color.Gray;
            this.ActivateButton.FillColor = colorPrimaryFrom;
            this.ActivateButton.FillColor2 = colorPrimaryTo;
            this.ActivateButton.Font = new System.Drawing.Font("Lucida Sans Unicode", 11F, System.Drawing.FontStyle.Bold);
            this.ActivateButton.ForeColor = System.Drawing.Color.White;
            this.ActivateButton.GradientMode = System.Drawing.Drawing2D.LinearGradientMode.ForwardDiagonal;
            this.ActivateButton.Image = global::iSkorpionA12.Properties.Resources.icons8_unlock_32;
            this.ActivateButton.ImageAlign = System.Windows.Forms.HorizontalAlignment.Left;
            this.ActivateButton.ImageSize = new System.Drawing.Size(25, 25);
            this.ActivateButton.IndicateFocus = true;
            this.ActivateButton.Location = new System.Drawing.Point(200, 300);
            this.ActivateButton.Name = "ActivateButton";
            this.ActivateButton.PressedColor = System.Drawing.Color.Transparent;
            this.ActivateButton.ShadowDecoration.BorderRadius = 25;
            this.ActivateButton.ShadowDecoration.Depth = 35;
            this.ActivateButton.ShadowDecoration.Enabled = true;
            this.ActivateButton.Size = new System.Drawing.Size(600, 70);
            this.ActivateButton.TabIndex = 18;
            this.ActivateButton.Text = "🚀 ACTIVATE DEVICE NOW";
            this.ActivateButton.UseTransparentBackground = true;
            this.ActivateButton.Click += new System.EventHandler(this.ActivateButton_Click);
            // 
            // guna2GradientButton2 (Block OTA / Reset - secondary)
            // 
            this.guna2GradientButton2.Animated = true;
            this.guna2GradientButton2.BackColor = System.Drawing.Color.Transparent;
            this.guna2GradientButton2.BorderColor = System.Drawing.Color.Transparent;
            this.guna2GradientButton2.BorderRadius = 25;
            this.guna2GradientButton2.Cursor = System.Windows.Forms.Cursors.Hand;
            this.guna2GradientButton2.DisabledState.BorderColor = System.Drawing.Color.FromArgb(200, 200, 200);
            this.guna2GradientButton2.DisabledState.FillColor = colorDisabled;
            this.guna2GradientButton2.DisabledState.FillColor2 = colorDisabled;
            this.guna2GradientButton2.Enabled = false;
            this.guna2GradientButton2.FillColor = colorDisabled;
            this.guna2GradientButton2.FillColor2 = colorDisabled;
            this.guna2GradientButton2.Font = new System.Drawing.Font("Segoe UI", 10F, System.Drawing.FontStyle.Bold);
            this.guna2GradientButton2.ForeColor = System.Drawing.Color.White;
            this.guna2GradientButton2.GradientMode = System.Drawing.Drawing2D.LinearGradientMode.ForwardDiagonal;
            this.guna2GradientButton2.ImageSize = new System.Drawing.Size(25, 25);
            this.guna2GradientButton2.IndicateFocus = true;
            this.guna2GradientButton2.Location = new System.Drawing.Point(200, 390);
            this.guna2GradientButton2.Name = "guna2GradientButton2";
            this.guna2GradientButton2.PressedColor = System.Drawing.Color.Transparent;
            this.guna2GradientButton2.ShadowDecoration.BorderRadius = 25;
            this.guna2GradientButton2.ShadowDecoration.Depth = 30;
            this.guna2GradientButton2.ShadowDecoration.Enabled = true;
            this.guna2GradientButton2.Size = new System.Drawing.Size(600, 60);
            this.guna2GradientButton2.TabIndex = 19;
            this.guna2GradientButton2.Text = "🛡️ BLOCK OTA";
            this.guna2GradientButton2.UseTransparentBackground = true;
            this.guna2GradientButton2.Click += new System.EventHandler(this.guna2GradientButton2_Click);
            // 
            // label2 (IMEI)
            // 
            this.label2.AutoSize = true;
            this.label2.BackColor = System.Drawing.Color.Transparent;
            this.label2.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.label2.ForeColor = colorTextSecondary;
            this.label2.Location = new System.Drawing.Point(750, 80);
            this.label2.Name = "label2";
            this.label2.Size = new System.Drawing.Size(32, 15);
            this.label2.TabIndex = 15;
            this.label2.Text = "IMEI";
            // 
            // guna2GradientButton1 (small left)
            // 
            this.guna2GradientButton1.Animated = true;
            this.guna2GradientButton1.BackColor = System.Drawing.Color.Transparent;
            this.guna2GradientButton1.BackgroundImage = ((System.Drawing.Image)(resources.GetObject("guna2GradientButton1.BackgroundImage")));
            this.guna2GradientButton1.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Stretch;
            this.guna2GradientButton1.BorderColor = System.Drawing.Color.Transparent;
            this.guna2GradientButton1.BorderRadius = 22;
            this.guna2GradientButton1.Cursor = System.Windows.Forms.Cursors.Hand;
            this.guna2GradientButton1.DisabledState.BorderColor = System.Drawing.Color.FromArgb(200, 200, 200);
            this.guna2GradientButton1.DisabledState.FillColor = System.Drawing.Color.Transparent;
            this.guna2GradientButton1.FillColor = System.Drawing.Color.Transparent;
            this.guna2GradientButton1.FillColor2 = System.Drawing.Color.Transparent;
            this.guna2GradientButton1.Font = new System.Drawing.Font("Lucida Sans Unicode", 11F, System.Drawing.FontStyle.Bold);
            this.guna2GradientButton1.ForeColor = System.Drawing.SystemColors.WindowText;
            this.guna2GradientButton1.GradientMode = System.Drawing.Drawing2D.LinearGradientMode.ForwardDiagonal;
            this.guna2GradientButton1.ImageSize = new System.Drawing.Size(30, 30);
            this.guna2GradientButton1.IndicateFocus = true;
            this.guna2GradientButton1.Location = new System.Drawing.Point(23, 36);
            this.guna2GradientButton1.Name = "guna2GradientButton1";
            this.guna2GradientButton1.PressedColor = System.Drawing.Color.Transparent;
            this.guna2GradientButton1.ShadowDecoration.BorderRadius = 4;
            this.guna2GradientButton1.ShadowDecoration.Depth = 28;
            this.guna2GradientButton1.ShadowDecoration.Enabled = true;
            this.guna2GradientButton1.ShadowDecoration.Shadow = new System.Windows.Forms.Padding(2);
            this.guna2GradientButton1.Size = new System.Drawing.Size(53, 52);
            this.guna2GradientButton1.TabIndex = 2;
            this.guna2GradientButton1.UseTransparentBackground = true;
            // 
            // label3 (main title)
            // 
            this.label3.AutoSize = true;
            this.label3.BackColor = System.Drawing.Color.Transparent;
            this.label3.Font = new System.Drawing.Font("Segoe UI", 18F, System.Drawing.FontStyle.Bold);
            this.label3.ForeColor = colorPrimaryFrom;
            this.label3.Location = new System.Drawing.Point(130, 35);
            this.label3.Name = "label3";
            this.label3.Size = new System.Drawing.Size(232, 21);
            this.label3.TabIndex = 17;
            this.label3.Text = "⚡ SKORPION ULTRA ACTIVATOR ⚡";
            this.label3.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            this.label3.Click += new System.EventHandler(this.label3_Click);
            // 
            // label4 (version small)
            // 
            this.label4.AutoSize = true;
            this.label4.BackColor = System.Drawing.Color.Transparent;
            this.label4.Font = new System.Drawing.Font("Segoe UI", 8.25F, System.Drawing.FontStyle.Regular);
            this.label4.ForeColor = colorTextSecondary;
            this.label4.Location = new System.Drawing.Point(130, 55);
            this.label4.Name = "label4";
            this.label4.Size = new System.Drawing.Size(113, 13);
            this.label4.TabIndex = 16;
            this.label4.Text = "Official version : v1.7";
            this.label4.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            // 
            // labelIMEI (value)
            // 
            this.labelIMEI.AutoSize = true;
            this.labelIMEI.BackColor = System.Drawing.Color.Transparent;
            this.labelIMEI.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.labelIMEI.ForeColor = System.Drawing.Color.FromArgb(51, 51, 51);
            this.labelIMEI.Location = new System.Drawing.Point(850, 80);
            this.labelIMEI.Name = "labelIMEI";
            this.labelIMEI.Size = new System.Drawing.Size(29, 15);
            this.labelIMEI.TabIndex = 26;
            this.labelIMEI.Text = "N/A";
            // 
            // label5 (footer)
            // 
            this.label5.AutoSize = true;
            this.label5.BackColor = System.Drawing.Color.Transparent;
            this.label5.Font = new System.Drawing.Font("Segoe UI", 8.25F, System.Drawing.FontStyle.Regular);
            this.label5.ForeColor = colorTextSecondary;
            this.label5.Location = new System.Drawing.Point(400, 540);
            this.label5.Name = "label5";
            this.label5.Size = new System.Drawing.Size(343, 13);
            this.label5.TabIndex = 27;
            this.label5.Text = "© 2018- 2025 Segredounlock.com  |  Segredo Activator  A12+ Tool";
            this.label5.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            // 
            // LogsBox (card style white)
            // 
            this.LogsBox.BackColor = System.Drawing.Color.Transparent;
            this.LogsBox.BorderColor = colorBorder;
            this.LogsBox.BorderRadius = 25;
            this.LogsBox.BorderThickness = 2;
            this.LogsBox.Cursor = System.Windows.Forms.Cursors.IBeam;
            this.LogsBox.DefaultText = "";
            this.LogsBox.DisabledState.BorderColor = System.Drawing.Color.FromArgb(208, 208, 208);
            this.LogsBox.DisabledState.FillColor = System.Drawing.Color.FromArgb(250, 250, 250);
            this.LogsBox.DisabledState.ForeColor = System.Drawing.Color.FromArgb(138, 138, 138);
            this.LogsBox.DisabledState.PlaceholderForeColor = System.Drawing.Color.FromArgb(138, 138, 138);
            this.LogsBox.FillColor = colorCard;
            this.LogsBox.FocusedState.BorderColor = colorPrimaryFrom;
            this.LogsBox.Font = new System.Drawing.Font("Segoe UI", 11F);
            this.LogsBox.ForeColor = System.Drawing.Color.FromArgb(54, 64, 67);
            this.LogsBox.HoverState.BorderColor = colorPrimaryFrom;
            this.LogsBox.Location = new System.Drawing.Point(50, 160);
            this.LogsBox.Multiline = true;
            this.LogsBox.Name = "LogsBox";
            this.LogsBox.PlaceholderText = "";
            this.LogsBox.ReadOnly = true;
            this.LogsBox.SelectedText = "";
            this.LogsBox.Size = new System.Drawing.Size(900, 120);
            this.LogsBox.TabIndex = 28;
            // 
            // labelECID
            // 
            this.labelECID.AutoSize = true;
            this.labelECID.BackColor = System.Drawing.Color.Transparent;
            this.labelECID.Font = new System.Drawing.Font("Segoe UI", 11F, System.Drawing.FontStyle.Bold);
            this.labelECID.ForeColor = System.Drawing.Color.FromArgb(51, 51, 51);
            this.labelECID.Location = new System.Drawing.Point(600, 540);
            this.labelECID.Name = "labelECID";
            this.labelECID.Size = new System.Drawing.Size(29, 15);
            this.labelECID.TabIndex = 29;
            this.labelECID.Text = "N/A";
            // 
            // Controls order
            // 
            this.Controls.Add(this.labelECID);
            this.Controls.Add(this.LogsBox);
            this.Controls.Add(this.label5);
            this.Controls.Add(this.labelIMEI);
            this.Controls.Add(this.label4);
            this.Controls.Add(this.label3);
            this.Controls.Add(this.guna2GradientButton1);
            this.Controls.Add(this.label2);
            this.Controls.Add(this.guna2CircleButton2);
            this.Controls.Add(this.labelActivaction);
            this.Controls.Add(this.Status);
            this.Controls.Add(this.pictureBox3);
            this.Controls.Add(this.label1);
            this.Controls.Add(this.guna2GradientButton3);
            this.Controls.Add(this.guna2CircleButton1);
            this.Controls.Add(this.guna2GradientButton2);
            this.Controls.Add(this.label16);
            this.Controls.Add(this.label15);
            this.Controls.Add(this.labelVersion);
            this.Controls.Add(this.label20);
            this.Controls.Add(this.ActivateButton);
            this.Controls.Add(this.labelSN);
            this.Controls.Add(this.ModeloffHello);
            this.Controls.Add(this.labelType);
            this.Controls.Add(this.Guna2ProgressBar1);
            this.Controls.Add(this.labelInfoProgres);
            this.Controls.Add(this.label23);
            this.Controls.Add(this.pictureBoxDC);
            this.Controls.Add(this.pictureBoxModel);

            this.DoubleBuffered = true;
            this.ForeColor = System.Drawing.Color.FromArgb(51, 51, 51);
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.None;
            this.Icon = ((System.Drawing.Icon)(resources.GetObject("$this.Icon")));
            this.MaximizeBox = false;
            this.MaximumSize = new System.Drawing.Size(851, 480);
            this.MinimizeBox = false;
            this.MinimumSize = new System.Drawing.Size(851, 480);
            this.Name = "Form1";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterScreen;
            this.Text = "Segredo Bypass Premium";
            this.TopMost = false;
            this.Load += new System.EventHandler(this.Form1_Load);

            ((System.ComponentModel.ISupportInitialize)(this.pictureBox3)).EndInit();
            ((System.ComponentModel.ISupportInitialize)(this.pictureBoxModel)).EndInit();
            ((System.ComponentModel.ISupportInitialize)(this.pictureBoxDC)).EndInit();
            this.ResumeLayout(false);
            this.PerformLayout();
        }

        #endregion

        internal System.Windows.Forms.PictureBox pictureBoxModel;
        private System.Windows.Forms.PictureBox pictureBox3;
        private System.Windows.Forms.Label labelType;
        private System.Windows.Forms.Label labelVersion;
        private System.Windows.Forms.Label labelSN;
        private System.Windows.Forms.Label ModeloffHello;
        private System.Windows.Forms.Label label15;
        private System.Windows.Forms.Label label16;
        private System.Windows.Forms.Label label20;
        private System.Windows.Forms.Label label23;
        internal System.Windows.Forms.PictureBox pictureBoxDC;
        private Guna.UI2.WinForms.Guna2CircleButton guna2CircleButton1;
        private Guna.UI2.WinForms.Guna2CircleButton guna2CircleButton2;
        private Guna.UI2.WinForms.Guna2Elipse guna2Elipse1;
        internal Guna.UI2.WinForms.Guna2GradientButton guna2GradientButton3;
        internal System.Windows.Forms.Label labelInfoProgres;
        internal Guna.UI2.WinForms.Guna2ProgressBar Guna2ProgressBar1;
        internal Guna.UI2.WinForms.Guna2GradientButton ActivateButton;
        internal System.Windows.Forms.Label label1;
        internal Guna.UI2.WinForms.Guna2GradientButton guna2GradientButton2;
        private System.Windows.Forms.Label Status;
        private System.Windows.Forms.Label labelActivaction;
        private System.Windows.Forms.Label label2;
        internal System.Windows.Forms.Label label3;
        internal Guna.UI2.WinForms.Guna2GradientButton guna2GradientButton1;
        internal System.Windows.Forms.Label label4;
        private System.Windows.Forms.Label labelIMEI;
        internal System.Windows.Forms.Label label5;
        private Guna.UI2.WinForms.Guna2TextBox LogsBox;
        private System.Windows.Forms.Label labelECID;
    }
}
