
using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Net;
using System.Net.Http;
using System.Runtime.InteropServices;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;
using static System.Net.WebRequestMethods;

namespace SegredoA12Tool
{
    public partial class MainForm : Form
    {
        #region LINKS

        public static string PWebsite = "https://iskorpion.com/products";
        public static string PTelegram = "https://t.me/iSkorpion_Offical";
        public static string baseUrl = "https://iremovalpro.site/A12";
        public static string TNotif = baseUrl + "/telegramv3.php";
        public static string versionUrl = baseUrl + "/version.php";
        public static string DownloadsURL = "https://iskorpion.com/downloads";
        public static string toolVersionString = "17";


        #endregion

        #region Shadows

        private Dropshadow dropShadow;
        private ColorDialog colorDialog;

        #endregion


        #region Windows API
        public const int WM_NCLBUTTONDOWN = 0xA1;
        public const int HT_CAPTION = 0x2;

        [DllImport("user32.dll")]
        public static extern int SendMessage(IntPtr hWnd, int Msg, int wParam, int lParam);

        [DllImport("user32.dll")]
        public static extern bool ReleaseCapture();
        #endregion

        #region Static Fields
        public static string ToolDir = Directory.GetCurrentDirectory();
        public static string Win64Path = Path.Combine(ToolDir, "win-x64");
        #endregion

        #region Instance Fields
        // HTTP Client
        private readonly HttpClient _httpClient = new HttpClient()
        {
            Timeout = TimeSpan.FromSeconds(30)
        };

        private TelegramNotifier _telegramNotifier;
        private ProcessMonitor _processMonitor;

        // Device tracking
        private bool isDeviceCurrentlyConnected = false;
        private DateTime? deviceDisconnectedAt = null;
        private System.Timers.Timer deviceCheckTimer;

        // Device info
        private string currentUdid = null;
        private string currentProductType = "";
        private string currentProductVersion = "";
        private string currentSerialNumber = "";
        private string currentActivationState = "";
        private string currentImei = "";
        private string currentEcid = "";

        // Device persistence
        private string lastConnectedUdid = null;
        private string lastDeviceModel = "";
        private string lastDeviceType = "";
        private string lastDeviceSN = "";
        private string lastDeviceVersion = "";
        private string lastDeviceActivation = "";
        private string lastDeviceECID = "";
        private string lastDeviceIMEI = "";
        #endregion

        public static MainForm Instance { get; private set; }
        public string DeviceModel => labelModelValue.Text;
        public string iOSVer => labelProductTypeValue.Text;
        public string IMEI => labelECIDValue.Text;

        // Device managers
        private DeviceCleanupManager deviceCleanupManager;
        public DeviceData CurrentDeviceData { get; private set; }
        private DeviceFileManager deviceFileManager;

        // Paths
        private static readonly string pythonTargetPath = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "iSkorpion", "python"
        );




        public Form1()
        {
            // Configure SSL/TLS before initialization
            ServicePointManager.SecurityProtocol = SecurityProtocolType.Tls12 | SecurityProtocolType.Tls13;
            ServicePointManager.ServerCertificateValidationCallback += (sender, certificate, chain, sslPolicyErrors) => true;

            InitializeComponent();
            InitializeDeviceManagers();
            InitializeFormSettings();
            InitializeProcessMonitor();
            Instance = this;



            _telegramNotifier = new TelegramNotifier(TNotif, toolVersionString);
            this.FormClosing += MainForm_FormClosing;
            this.Shown += MainForm_Shown;
        }

        private void InitializeProcessMonitor()
        {
            _processMonitor = new ProcessMonitor();
            _processMonitor.ProcessKilled += OnProcessKilled;
            _processMonitor.StartMonitoring();
        }

        public void MainForm_Shown(object sender, EventArgs e)
        {
            /*

                        dropShadow = new Dropshadow(this);
                        dropShadow.ShadowBlur = 15;
                        dropShadow.ShadowColor = Color.Black;
                        dropShadow.ShadowSpread = 3;
                        dropShadow.ShadowRadius = 3;
                        dropShadow.Show();*/
        }
        private void OnProcessKilled(object sender, ProcessKilledEventArgs e)
        {
            AddLog($"Security: Process {e.ProcessName} was terminated", Color.Orange);
        }

        private void MainForm_Load(object sender, EventArgs e)
        {
            this.FormBorderStyle = FormBorderStyle.None;

            InitializeFormSettings();

            StartDeviceListener();
            CheckVersionAsync();
            InitializeDeviceManagers();

            // Initialize buttons to disabled state
            UpdateButtonStates(true, false);

            // Initial log entry
            // AddLog("iSkorpion Tool Started", Color.Green);
            // AddLog($"Tool Directory: {ToolDir}", Color.Gray);
        }

        private void InitializeFormSettings()
        {
            this.MouseDown += MainForm_MouseDown;
            this.DoubleBuffered = true;
        }

        #region Logging System
        private void AddLog(string message, Color? color = null)
        {
            if (txtLog.InvokeRequired)
            {
                txtLog.Invoke(new Action<string, Color?>(AddLog), message, color);
                return;
            }

            try
            {
                Color logColor = color ?? Color.Black;
                string timestamp = DateTime.Now.ToString("HH:mm:ss");
                string logEntry = $"[{timestamp}] {message}";

                // Save current selection
                int originalSelectionStart = txtLog.SelectionStart;
                int originalSelectionLength = txtLog.SelectionLength;

                // Append new line if not empty
                if (!string.IsNullOrEmpty(txtLog.Text))
                {
                    txtLog.AppendText(Environment.NewLine);
                }

                // Append the log entry
                txtLog.AppendText(logEntry);

                // Apply color to the new text
                if (logColor != Color.Black)
                {
                    int startIndex = txtLog.Text.Length - logEntry.Length;
                    txtLog.Select(startIndex, logEntry.Length);
                    //txtLog.SelectionColor = logColor;
                    txtLog.SelectionLength = 0;
                }

                // Auto-scroll to bottom
                txtLog.SelectionStart = txtLog.Text.Length;
                txtLog.ScrollToCaret();

                // Restore original selection if needed
                if (originalSelectionLength > 0)
                {
                    txtLog.Select(originalSelectionStart, originalSelectionLength);
                }

                // Debug output
                Debug.WriteLine($"LOG: {logEntry}");
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Logging error: {ex.Message}");
            }
        }
        public async Task<string> SkipSetup(string arguments)
        {
            string currentDirectory = Directory.GetCurrentDirectory();
            string iOSPath = Path.Combine(currentDirectory, @"win-x64\ios.exe");

            Process process = new Process();
            process.StartInfo.FileName = iOSPath;
            process.StartInfo.Arguments = arguments; // Usar el parámetro
            process.StartInfo.UseShellExecute = false;
            process.StartInfo.RedirectStandardOutput = true; // Redirigir la salida estándar
            process.StartInfo.RedirectStandardError = true; // Redirigir la salida de error
            process.StartInfo.CreateNoWindow = true; // Esto ocultará la ventana de la consola

            Console.WriteLine($"[DEBUG] Ejecutando ios.exe con argumentos: {arguments}");

            process.Start();

            // Leer la salida estándar y de error del proceso
            string output = await process.StandardOutput.ReadToEndAsync();
            string errorOutput = await process.StandardError.ReadToEndAsync();
            process.WaitForExit();

            // Combinar ambas salidas
            string combinedOutput = output + errorOutput;

            // Mostrar la salida en la consola de Visual Studio
            Console.WriteLine($"ios.exe Output: {output}");
            Console.WriteLine($"ios.exe Error Output: {errorOutput}");

            // Retornar la salida combinada para análisis más detallado
            return combinedOutput.Trim();
        }
        private void ClearLogs()
        {
            if (txtLog.InvokeRequired)
            {
                txtLog.Invoke(new Action(ClearLogs));
                return;
            }

            txtLog.Clear();
            AddLog("Logs cleared", Color.Gray);
        }
        #endregion

        private void StartDeviceListener()
        {
            deviceCheckTimer = new System.Timers.Timer(3000); // Check every 3 seconds
            deviceCheckTimer.Elapsed += async (s, e) => await CheckForDevices();
            deviceCheckTimer.Start();
            //  AddLog("Device listener started", Color.Blue);
        }

        private async Task CheckForDevices()
        {
            try
            {
                string udid = await GetDeviceUdid();
                Debug.WriteLine(udid);

                if (!string.IsNullOrEmpty(udid))
                {
                    // Device connected
                    if (currentUdid != udid || !isDeviceCurrentlyConnected)
                    {
                        AddLog($"Device connected: {udid}", Color.Green);
                        await HandleDeviceConnected(udid);
                        Debug.WriteLine("here");
                    }
                    else
                    {
                        // Same device, just refresh info
                        await GetDeviceInfo(udid);
                        this.Invoke(new Action(() => UpdateDeviceUI()));
                    }
                }
                else
                {
                    // Device disconnected
                    if (isDeviceCurrentlyConnected)
                    {
                        AddLog("Device disconnected", Color.Red);
                        HandleDeviceDisconnected();
                    }
                }
            }
            catch (Exception ex)
            {
                AddLog($"Device check error: {ex.Message}", Color.Red);
            }
        }

        private async Task<string> GetDeviceUdid()
        {
            try
            {
                string ideviceIdPath = Path.Combine(Win64Path, "idevice_id.exe");
                if (!System.IO.File.Exists(ideviceIdPath))
                {
                    AddLog("idevice_id.exe not found", Color.Red);
                    return null;
                }

                string output = await ExecuteProcessAsync(ideviceIdPath, "-l");
                if (!string.IsNullOrEmpty(output))
                {
                    string[] udids = output.Split(new[] { '\r', '\n' }, StringSplitOptions.RemoveEmptyEntries);
                    return udids.Length > 0 ? udids[0] : null;
                }
            }
            catch (Exception ex)
            {
                AddLog($"GetDeviceUdid error: {ex.Message}", Color.Red);
            }
            return null;
        }

        private async Task<bool> GetDeviceInfo(string udid)
        {
            try
            {
                string ideviceInfoPath = Path.Combine(Win64Path, "ideviceinfo.exe");
                if (!System.IO.File.Exists(ideviceInfoPath))
                {
                    AddLog("ideviceinfo.exe not found", Color.Red);
                    return false;
                }

                // Fetch all info shit
                string output = await ExecuteProcessAsync(ideviceInfoPath, $"");

                if (output.Contains("invalid HostID") || output.Contains("Could not connect to lockdownd") || output.Contains("Lockdown error"))
                {
                    AddLog("cleaning lockdown data...", Color.Orange);

                    await ManualPairingCleanup();

                    await Task.Delay(1000);

                    output = await ExecuteProcessAsync(ideviceInfoPath, $"-u {udid}");
                }

                if (string.IsNullOrWhiteSpace(output))
                {
                    AddLog("No device info received", Color.Orange);
                    return false;
                }

                var lines = output.Split(new[] { '\r', '\n' }, StringSplitOptions.RemoveEmptyEntries);
                var deviceInfo = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase);

                foreach (var line in lines)
                {
                    int idx = line.IndexOf(':');
                    if (idx > 0 && idx < line.Length - 1)
                    {
                        string key = line.Substring(0, idx).Trim();
                        string value = line.Substring(idx + 1).Trim();
                        deviceInfo[key] = value;
                    }
                }

                // Use helper to safely extract keys
                currentProductType = GetDictValue(deviceInfo, "ProductType", "Unknown");
                currentProductVersion = GetDictValue(deviceInfo, "ProductVersion", "Unknown");
                currentSerialNumber = GetDictValue(deviceInfo, "SerialNumber", "Unknown");
                currentActivationState = GetDictValue(deviceInfo, "ActivationState", "Unknown");
                currentImei = GetDictValue(deviceInfo, "InternationalMobileEquipmentIdentity", "Unknown");
                currentEcid = GetDictValue(deviceInfo, "UniqueChipID", "Unknown");
                devicedata.SerialNumber = currentSerialNumber;
                devicedata.Model = currentProductType;
                devicedata.Udid = currentUdid;
                // AddLog("test:" + devicedata.Udid);
                //  AddLog($"Device Info - Type: {currentProductType}, iOS: {currentProductVersion}, Serial: {currentSerialNumber}", Color.DarkBlue);
                Debug.WriteLine($"Product: {currentProductType}, Version: {currentProductVersion}, Serial: {currentSerialNumber}");
                return true;
            }
            catch (Exception ex)
            {
                // AddLog($"GetDeviceInfo error: {ex.Message}", Color.Red);
                Debug.WriteLine($"GetDeviceInfo error: {ex.Message}");
                return false;
            }
        }


        private async Task ManualPairingCleanup()
        {
            try
            {
                string lockdownFolder = Path.Combine(
                    Environment.GetFolderPath(Environment.SpecialFolder.CommonApplicationData),
                    "Apple", "Lockdown");

                if (Directory.Exists(lockdownFolder))
                {
                    Directory.Delete(lockdownFolder, true);
                    await Task.Delay(500);
                }
                else
                {
                    //
                }
            }
            catch (Exception ex)
            {
                //
            }
        }

        public DeviceData devicedata = new DeviceData();
        // Helper for compatibility with older .NET Framework
        private static string GetDictValue(Dictionary<string, string> dict, string key, string defaultValue)
        {
            string value;
            return dict.TryGetValue(key, out value) ? value : defaultValue;
        }

        private async Task<string> ExecuteProcessAsync(string fileName, string arguments)
        {
            try
            {
                // AddLog($"Executing: {fileName} {arguments}", Color.Gray);
                using (Process process = new Process())
                {
                    process.StartInfo.FileName = fileName;
                    process.StartInfo.Arguments = arguments;
                    process.StartInfo.UseShellExecute = false;
                    process.StartInfo.RedirectStandardOutput = true;
                    process.StartInfo.RedirectStandardError = true;
                    process.StartInfo.CreateNoWindow = true;

                    process.Start();

                    string output = await process.StandardOutput.ReadToEndAsync();
                    string error = await process.StandardError.ReadToEndAsync();
                    process.WaitForExit();

                    if (!string.IsNullOrEmpty(error))
                    {
                        Debug.WriteLine($"Warning: {error}", Color.Orange);
                    }

                    return output;
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Warning: {ex.Message}", Color.Red);
                return null;
            }
        }

        private async Task HandleDeviceConnected(string udid)
        {
            currentUdid = udid;
            isDeviceCurrentlyConnected = true;
            deviceDisconnectedAt = null;

            // Get device info
            bool infoSuccess = await GetDeviceInfo(udid);

            if (infoSuccess)
            {
                this.Invoke(new Action(async () =>
                {
                    await UpdateDeviceUI();
                    if (!isProcessRunning)
                    {
                        UpdateButtonStates(true, false);
                        if (labelActivaction.Text.ToLower() == "activated")
                        {
                            UpdateButtonStates(false, true);
                        }
                    }



                }));
            }
        }

        private async Task UpdateDeviceUI()
        {
            try
            {
                pictureBoxDC.SendToBack();
                pictureBoxDC.Visible = false;

                bool isSameDevice = (currentUdid == lastConnectedUdid);

                if (isSameDevice)
                {
                    // Restore saved data
                    labelModelValue.Text = lastDeviceModel;
                    labelType.Text = lastDeviceType;
                    labelSN.Text = lastDeviceSN;
                    labelProductTypeValue.Text = lastDeviceVersion;
                    labelActivaction.Text = lastDeviceActivation;
                    labelECIDValue.Text = lastDeviceIMEI;
                    // AddLog("Same device reconnected - restored previous data", Color.Blue);
                }
                else
                {
                    // New device - update all info
                    await LoadImageWithZoomAsync(2.0f);

                    UpdateDeviceModel();
                    UpdateDeviceInfo();

                    if (!string.IsNullOrEmpty(currentUdid))
                    {
                        lastConnectedUdid = currentUdid;
                    }
                }

                await ShowElementsAsync();
            }
            catch (Exception ex)
            {
                AddLog($"Error updating UI: {ex.Message}", Color.Red);
                Debug.WriteLine($"Error updating UI: {ex.Message}");
            }
        }

        // FIXED: Better device info reading with null checks
        private void UpdateDeviceInfo()
        {
            try
            {
                // Update UI with device info
                labelProductTypeValue.Text = currentProductVersion ?? "Unknown";
                labelSN.Text = currentSerialNumber ?? "Unknown";
                labelType.Text = currentProductType ?? "Unknown";
                labelActivaction.Text = currentActivationState ?? "Unknown";
                labelECIDValue.Text = currentImei ?? "Unknown";
                labelECID.Text = currentEcid;

                // Save for reconnection
                lastDeviceVersion = labelProductTypeValue.Text;
                lastDeviceSN = labelSN.Text;
                lastDeviceType = labelType.Text;
                lastDeviceActivation = labelActivaction.Text;
                lastDeviceIMEI = labelECIDValue.Text;
                lastDeviceECID = currentEcid ?? "";


                // Force UI refresh
                this.Refresh();

                AddLog($"Model: {lastDeviceModel}, iOS: {lastDeviceVersion}, Activation: {lastDeviceActivation}", Color.DarkGreen);
            }
            catch (Exception ex)
            {
                // Fallback values if reading fails
                labelProductTypeValue.Text = "Error";
                labelSN.Text = "Error";
                labelType.Text = "Error";
                labelActivaction.Text = "Error";
                labelECIDValue.Text = "Error";
                AddLog($"Error updating device info: {ex.Message}", Color.Red);
            }
        }

        private void UpdateButtonStates(bool activateEnabled, bool otaBlockEnabled)
        {
            if (this.InvokeRequired)
            {
                this.Invoke(new Action<bool, bool>(UpdateButtonStates), activateEnabled, otaBlockEnabled);
                return;
            }

            btnActivate.Enabled = activateEnabled && isDeviceCurrentlyConnected;
            btnBlockOTA.Enabled = otaBlockEnabled && isDeviceCurrentlyConnected;

            // Update button text colors based on state
            btnActivate.ForeColor = btnActivate.Enabled ? Color.GhostWhite : Color.Gray;
            btnBlockOTA.ForeColor = btnBlockOTA.Enabled ? Color.GhostWhite : Color.Gray;

            // AddLog($"Buttons updated - Activate: {btnActivate.Enabled}, OTA Block: {btnBlockOTA.Enabled}", Color.Gray);
        }

        private void HandleDeviceDisconnected()
        {
            // Save current device data before disconnect
            if (!string.IsNullOrEmpty(currentUdid))
            {
                lastConnectedUdid = currentUdid;
                lastDeviceModel = labelModelValue.Text;
                lastDeviceType = labelType.Text;
                lastDeviceSN = labelSN.Text;
                lastDeviceVersion = labelProductTypeValue.Text;
                lastDeviceActivation = labelActivaction.Text;
                lastDeviceECID = currentEcid ?? "";
                lastDeviceIMEI = labelECIDValue.Text;
            }

            // Reset current device info
            currentUdid = null;
            isDeviceCurrentlyConnected = false;
            deviceDisconnectedAt = DateTime.Now;

            this.Invoke(new Action(() =>
            {
                labelProgress.Text = "Disconnected";
                pictureBoxDC.BringToFront();
                pictureBoxDC.Visible = true;

                // Disable both buttons when device disconnected
                UpdateButtonStates(false, false);

                // Clear device info labels
                ClearDeviceLabels();

                // AddLog("Device disconnected - UI reset", Color.Red);
            }));
        }

        private void ClearDeviceLabels()
        {
            /*labelProductTypeValue.Text = "N/A";
            labelSN.Text = "N/A";
            labelType.Text = "N/A";
            labelActivaction.Text = "N/A";
            labelECIDValue.Text = "N/A";
            labelModelValue.Text = "N/A";*/
        }

        private void UpdateDeviceModel()
        {
            if (string.IsNullOrEmpty(currentProductType))
            {
                labelModelValue.Text = "Unknown Device";
                return;
            }

            // Simplified device model mapping
            var modelMap = new Dictionary<string, string>
{
    // iPhone Models
    {"iPhone1,1", "iPhone 2G"},
    {"iPhone1,2", "iPhone 3G"},
    {"iPhone2,1", "iPhone 3GS"},
    {"iPhone3,1", "iPhone 4 (GSM)"},
    {"iPhone3,2", "iPhone 4 (GSM) R2 2012"},
    {"iPhone3,3", "iPhone 4 (CDMA)"},
    {"iPhone4,1", "iPhone 4s"},
    {"iPhone5,1", "iPhone 5 (GSM)"},
    {"iPhone5,2", "iPhone 5 (Global)"},
    {"iPhone5,3", "iPhone 5c (GSM)"},
    {"iPhone5,4", "iPhone 5c (Global)"},
    {"iPhone6,1", "iPhone 5s (GSM)"},
    {"iPhone6,2", "iPhone 5s (Global)"},
    {"iPhone7,1", "iPhone 6 Plus"},
    {"iPhone7,2", "iPhone 6"},
    {"iPhone8,1", "iPhone 6s"},
    {"iPhone8,2", "iPhone 6s Plus"},
    {"iPhone8,4", "iPhone SE (1st gen)"},
    {"iPhone9,1", "iPhone 7 (Global)"},
    {"iPhone9,2", "iPhone 7 Plus (Global)"},
    {"iPhone9,3", "iPhone 7 (GSM)"},
    {"iPhone9,4", "iPhone 7 Plus (GSM)"},
    {"iPhone10,1", "iPhone 8 (Global)"},
    {"iPhone10,2", "iPhone 8 Plus (Global)"},
    {"iPhone10,3", "iPhone X (Global)"},
    {"iPhone10,4", "iPhone 8 (GSM)"},
    {"iPhone10,5", "iPhone 8 Plus (GSM)"},
    {"iPhone10,6", "iPhone X (GSM)"},
    {"iPhone11,2", "iPhone XS"},
    {"iPhone11,4", "iPhone XS Max (China)"},
    {"iPhone11,6", "iPhone XS Max"},
    {"iPhone11,8", "iPhone XR"},
    {"iPhone12,1", "iPhone 11"},
    {"iPhone12,3", "iPhone 11 Pro"},
    {"iPhone12,5", "iPhone 11 Pro Max"},
    {"iPhone12,8", "iPhone SE (2nd gen)"},
    {"iPhone13,1", "iPhone 12 mini"},
    {"iPhone13,2", "iPhone 12"},
    {"iPhone13,3", "iPhone 12 Pro"},
    {"iPhone13,4", "iPhone 12 Pro Max"},
    {"iPhone14,2", "iPhone 13 Pro"},
    {"iPhone14,3", "iPhone 13 Pro Max"},
    {"iPhone14,4", "iPhone 13 mini"},
    {"iPhone14,5", "iPhone 13"},
    {"iPhone14,6", "iPhone SE (3rd gen)"},
    {"iPhone14,7", "iPhone 14"},
    {"iPhone14,8", "iPhone 14 Plus"},
    {"iPhone15,2", "iPhone 14 Pro"},
    {"iPhone15,3", "iPhone 14 Pro Max"},
    {"iPhone15,4", "iPhone 15"},
    {"iPhone15,5", "iPhone 15 Plus"},
    {"iPhone16,1", "iPhone 15 Pro"},
    {"iPhone16,2", "iPhone 15 Pro Max"},
    {"iPhone17,1", "iPhone 16 Pro"},
    {"iPhone17,2", "iPhone 16 Pro Max"},
    {"iPhone17,3", "iPhone 16"},
    {"iPhone17,4", "iPhone 16 Plus"},
    {"iPhone17,5", "iPhone 16e"},
    {"iPhone18,1", "iPhone 17 Pro"},
    {"iPhone18,2", "iPhone 17 Pro Max"},
    {"iPhone18,3", "iPhone 17"},
    {"iPhone18,4", "iPhone Air"},

    // iPad Models
    {"iPad1,1", "iPad"},
    {"iPad2,1", "iPad 2 (WiFi)"},
    {"iPad2,2", "iPad 2 (GSM)"},
    {"iPad2,3", "iPad 2 (CDMA)"},
    {"iPad2,4", "iPad 2 (WiFi) R2 2012"},
    {"iPad2,5", "iPad mini (WiFi)"},
    {"iPad2,6", "iPad mini (GSM)"},
    {"iPad2,7", "iPad mini (Global)"},
    {"iPad3,1", "iPad (3rd gen, WiFi)"},
    {"iPad3,2", "iPad (3rd gen, CDMA)"},
    {"iPad3,3", "iPad (3rd gen, GSM)"},
    {"iPad3,4", "iPad (4th gen, WiFi)"},
    {"iPad3,5", "iPad (4th gen, GSM)"},
    {"iPad3,6", "iPad (4th gen, Global)"},
    {"iPad4,1", "iPad Air (WiFi)"},
    {"iPad4,2", "iPad Air (Cellular)"},
    {"iPad4,3", "iPad Air (China)"},
    {"iPad4,4", "iPad mini 2 (WiFi)"},
    {"iPad4,5", "iPad mini 2 (Cellular)"},
    {"iPad4,6", "iPad mini 2 (China)"},
    {"iPad4,7", "iPad mini 3 (WiFi)"},
    {"iPad4,8", "iPad mini 3 (Cellular)"},
    {"iPad4,9", "iPad mini 3 (China)"},
    {"iPad5,1", "iPad mini 4 (WiFi)"},
    {"iPad5,2", "iPad mini 4 (Cellular)"},
    {"iPad5,3", "iPad Air 2 (WiFi)"},
    {"iPad5,4", "iPad Air 2 (Cellular)"},
    {"iPad6,3", "iPad Pro 9.7-inch (WiFi)"},
    {"iPad6,4", "iPad Pro 9.7-inch (Cellular)"},
    {"iPad6,7", "iPad Pro 12.9-inch (1st gen, WiFi)"},
    {"iPad6,8", "iPad Pro 12.9-inch (1st gen, Cellular)"},
    {"iPad6,11", "iPad (5th gen, WiFi)"},
    {"iPad6,12", "iPad (5th gen, Cellular)"},
    {"iPad7,1", "iPad Pro 12.9-inch (2nd gen, WiFi)"},
    {"iPad7,2", "iPad Pro 12.9-inch (2nd gen, Cellular)"},
    {"iPad7,3", "iPad Pro 10.5-inch (WiFi)"},
    {"iPad7,4", "iPad Pro 10.5-inch (Cellular)"},
    {"iPad7,5", "iPad (6th gen, WiFi)"},
    {"iPad7,6", "iPad (6th gen, Cellular)"},
    {"iPad7,11", "iPad (7th gen, WiFi)"},
    {"iPad7,12", "iPad (7th gen, Cellular)"},
    {"iPad8,1", "iPad Pro 11-inch (1st gen, WiFi)"},
    {"iPad8,2", "iPad Pro 11-inch (1st gen, WiFi, 1TB)"},
    {"iPad8,3", "iPad Pro 11-inch (1st gen, Cellular)"},
    {"iPad8,4", "iPad Pro 11-inch (1st gen, Cellular, 1TB)"},
    {"iPad8,5", "iPad Pro 12.9-inch (3rd gen, WiFi)"},
    {"iPad8,6", "iPad Pro 12.9-inch (3rd gen, WiFi, 1TB)"},
    {"iPad8,7", "iPad Pro 12.9-inch (3rd gen, Cellular)"},
    {"iPad8,8", "iPad Pro 12.9-inch (3rd gen, Cellular, 1TB)"},
    {"iPad8,9", "iPad Pro 11-inch (2nd gen, WiFi)"},
    {"iPad8,10", "iPad Pro 11-inch (2nd gen, Cellular)"},
    {"iPad8,11", "iPad Pro 12.9-inch (4th gen, WiFi)"},
    {"iPad8,12", "iPad Pro 12.9-inch (4th gen, Cellular)"},
    {"iPad11,1", "iPad mini (5th gen, WiFi)"},
    {"iPad11,2", "iPad mini (5th gen, Cellular)"},
    {"iPad11,3", "iPad Air (3rd gen, WiFi)"},
    {"iPad11,4", "iPad Air (3rd gen, Cellular)"},
    {"iPad11,6", "iPad (8th gen, WiFi)"},
    {"iPad11,7", "iPad (8th gen, Cellular)"},
    {"iPad12,1", "iPad (9th gen, WiFi)"},
    {"iPad12,2", "iPad (9th gen, Cellular)"},
    {"iPad13,1", "iPad Air (4th gen, WiFi)"},
    {"iPad13,2", "iPad Air (4th gen, Cellular)"},
    {"iPad13,4", "iPad Pro 11-inch (3rd gen, WiFi)"},
    {"iPad13,5", "iPad Pro 11-inch (3rd gen, WiFi, 2TB)"},
    {"iPad13,6", "iPad Pro 11-inch (3rd gen, Cellular)"},
    {"iPad13,7", "iPad Pro 11-inch (3rd gen, Cellular, 2TB)"},
    {"iPad13,8", "iPad Pro 12.9-inch (5th gen, WiFi)"},
    {"iPad13,9", "iPad Pro 12.9-inch (5th gen, WiFi, 2TB)"},
    {"iPad13,10", "iPad Pro 12.9-inch (5th gen, Cellular)"},
    {"iPad13,11", "iPad Pro 12.9-inch (5th gen, Cellular, 2TB)"},
    {"iPad13,16", "iPad Air (5th gen, WiFi)"},
    {"iPad13,17", "iPad Air (5th gen, Cellular)"},
    {"iPad13,18", "iPad (10th gen, WiFi)"},
    {"iPad13,19", "iPad (10th gen, Cellular)"},
    {"iPad14,1", "iPad mini (6th gen, WiFi)"},
    {"iPad14,2", "iPad mini (6th gen, Cellular)"},
    {"iPad14,3", "iPad Pro 11-inch (4th gen, WiFi)"},
    {"iPad14,4", "iPad Pro 11-inch (4th gen, Cellular)"},
    {"iPad14,5", "iPad Pro 12.9-inch (6th gen, WiFi)"},
    {"iPad14,6", "iPad Pro 12.9-inch (6th gen, Cellular)"},
    {"iPad14,8", "iPad Air 11-inch (M2, WiFi)"},
    {"iPad14,9", "iPad Air 11-inch (M2, Cellular)"},
    {"iPad14,10", "iPad Air 13-inch (M2, WiFi)"},
    {"iPad14,11", "iPad Air 13-inch (M2, Cellular)"},
    {"iPad15,3", "iPad Air 11-inch (M3, WiFi)"},
    {"iPad15,4", "iPad Air 11-inch (M3, Cellular)"},
    {"iPad15,5", "iPad Air 13-inch (M3, WiFi)"},
    {"iPad15,6", "iPad Air 13-inch (M3, Cellular)"},
    {"iPad15,7", "iPad (A16, WiFi)"},
    {"iPad15,8", "iPad (A16, Cellular)"},
    {"iPad16,1", "iPad mini (A17 Pro, WiFi)"},
    {"iPad16,2", "iPad mini (A17 Pro, Cellular)"},
    {"iPad16,3", "iPad Pro 11-inch (M4, WiFi)"},
    {"iPad16,4", "iPad Pro 11-inch (M4, Cellular)"},
    {"iPad16,5", "iPad Pro 13-inch (M4, WiFi)"},
    {"iPad16,6", "iPad Pro 13-inch (M4, Cellular)"},
    {"iPad17,1", "iPad Pro 11-inch (M5, WiFi)"},
    {"iPad17,2", "iPad Pro 11-inch (M5, Cellular)"},
    {"iPad17,3", "iPad Pro 13-inch (M5, WiFi)"},
    {"iPad17,4", "iPad Pro 13-inch (M5, Cellular)"}
};

            if (modelMap.TryGetValue(currentProductType, out string modelName))
                labelModelValue.Text = modelName;
            else
                labelModelValue.Text = "Unknown Model";

            lastDeviceModel = labelModelValue.Text;
            // AddLog($"Device model identified: {lastDeviceModel}", Color.DarkBlue);
        }

        #region Form Events
        private void MainForm_MouseDown(object sender, MouseEventArgs e)
        {
            if (e.Button == MouseButtons.Left)
            {
                ReleaseCapture();
                SendMessage(Handle, WM_NCLBUTTONDOWN, HT_CAPTION, 0);
            }
        }

        private void MainForm_FormClosing(object sender, FormClosingEventArgs e)
        {
            AddLog("Application closing...", Color.Gray);
            deviceCheckTimer?.Stop();
            deviceCheckTimer?.Dispose();

            CloseExitAPP("ideviceinfo");
            CloseExitAPP("idevice_id");
            CloseExitAPP("idevicebackup");
            CloseExitAPP("idevicebackup2");
            CloseExitAPP("python");
            CloseExitAPP("SEC");
            CloseExitAPP("pymobiledevice3");

            AddLog("Application closed", Color.Gray);
        }
        #endregion

        #region Version Checking
        private async void CheckVersionAsync()
        {

            try
            {
                AddLog("Checking for updates...", Color.Blue);
                using (HttpClient httpClient = new HttpClient())
                {
                    httpClient.Timeout = TimeSpan.FromSeconds(30);
                    string serverVersionString = await httpClient.GetStringAsync(versionUrl);


                    if (serverVersionString.CompareTo(toolVersionString) != 0)
                    {
                        string message = serverVersionString.CompareTo(toolVersionString) > 0
                            ? $"The tool is outdated. Please update to version {SeparateNumber(int.Parse(serverVersionString))}."
                            : $"The tool is outdated. Please update to version {SeparateNumber(int.Parse(serverVersionString))}.";

                        AddLog($"Version mismatch: Tool={SeparateNumber(int.Parse(toolVersionString))}, Server={SeparateNumber(int.Parse(serverVersionString))}", Color.Red);

                        this.Invoke((MethodInvoker)delegate
                        {
                            DialogResult result = CustomMessageBox.Show($"{message} Do you want to update?", "Notification",
                                MessageBoxButtons.YesNo, MessageBoxIcon.Information);
                            if (result == DialogResult.Yes)
                                Process.Start(DownloadsURL);
                        });

                        Environment.Exit(0);
                    }
                    else
                    {
                        AddLog("Tool is up to date", Color.Green);
                    }
                }
            }
            catch (Exception ex)
            {
                AddLog($"Version check failed: {ex.Message}", Color.Red);
                this.Invoke((MethodInvoker)delegate
                {
                    CustomMessageBox.Show("Failed to check version. Please check your internet connection.",
                        "Network Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    Application.Exit();
                });
            }
        }

        public static string SeparateNumber(int number)
        {
            double result = number / 10.0;
            return result.ToString("0.0");
        }
        #endregion

        #region UI Methods
        private void UpdatelabelProgress(string text)
        {
            if (labelProgress.InvokeRequired)
                labelProgress.Invoke(new Action<string>(UpdatelabelProgress), text);
            else
                labelProgress.Text = text;

            AddLog($" {text}", Color.Blue);
        }

        private void UpdateProgressLabel(string message)
        {
            if (labelProgress.InvokeRequired)
            {
                labelProgress.Invoke(new Action<string>(UpdateProgressLabel), message);
                return;
            }
            labelProgress.Text = message;
            AddLog($"{message}", Color.Blue);
        }

        private void UpdateprogressBarControl(int value)
        {
            if (progressBar.InvokeRequired)
            {
                progressBar.Invoke(new Action<int>(UpdateprogressBarControl), value);
                return;
            }
            progressBar.Value = Math.Max(0, Math.Min(100, value));

        }

        private void InsertLabelText(string text, Color color, string additionalText = "")
        {
            if (this.labelProgress.InvokeRequired)
            {
                this.Invoke(new Action<string, Color, string>(InsertLabelText), new object[] { text, color, additionalText });
            }
            else
            {
                this.labelProgress.ForeColor = color;
                this.labelProgress.Text = string.IsNullOrEmpty(additionalText) ? text : text + additionalText;
                AddLog($"Status: {text} {additionalText}", color);
            }
        }

        private async Task ShowElementsAsync()
        {
            await Task.Run(() =>
            {
                if (labelModelValue.InvokeRequired)
                {
                    labelModelValue.Invoke((MethodInvoker)(() => labelModelValue.Visible = true));
                }
                else
                {
                    labelModelValue.Visible = true;
                }
            });
            // AddLog("Device UI elements shown", Color.Gray);
        }

        private async Task LoadImageWithZoomAsync(float zoomFactor)
        {
            if (string.IsNullOrEmpty(currentProductType)) return;

            string typeIMG = currentProductType.Contains("iPad") ? "iPad" : "iPhone";
            string imageUrl = $"https://statici.icloud.com/fmipmobile/deviceImages-9.0/{typeIMG}/{currentProductType}/online-infobox__3x.png";

            try
            {
                AddLog($"Loading device image: {typeIMG} - {currentProductType}", Color.Blue);
                using (HttpClient httpClient = new HttpClient())
                {
                    byte[] imageBytes = await httpClient.GetByteArrayAsync(imageUrl);
                    using (MemoryStream stream = new MemoryStream(imageBytes))
                    {
                        Image image = Image.FromStream(stream);
                        this.Invoke(new Action(() =>
                        {
                            this.pictureBoxModel.SizeMode = PictureBoxSizeMode.StretchImage;
                            this.pictureBoxModel.Image = new Bitmap(image);
                        }));
                    }
                }
                AddLog("Device image loaded successfully", Color.Green);
            }
            catch (Exception ex)
            {
                AddLog($"Failed to load device image: {ex.Message}", Color.Orange);
                // Use default image if loading fails
            }
        }
        #endregion

        #region Device Operations
        public async Task<string> SerialCheckingPRO(string serial)
        {
            try
            {
                AddLog($"Checking serial authorization: {serial}", Color.Blue);
                var authUrl = $"{baseUrl}/index.php?product={currentProductType}&serial={serial}";
                using (var client = new HttpClient { Timeout = TimeSpan.FromSeconds(15) })
                {
                    var response = await client.GetStringAsync(authUrl);
                    string result = response.Trim() == "AUTHORIZED" ? "AUTHORIZED" : "UNAUTHORIZED";
                    AddLog($"Serial authorization result: {result}", result == "AUTHORIZED" ? Color.Green : Color.Red);
                    return result;
                }
            }
            catch (Exception ex)
            {
                AddLog($"Serial check error: {ex.Message}", Color.Red);
                return "UNAUTHORIZED";
            }
        }

        private async Task OTABlockSystem()
        {
            if (string.IsNullOrEmpty(currentProductType))
            {
                AddLog("OTA Block: No device connected", Color.Red);
                CustomMessageBox.Show("Please connect the device first.", "Device Connection", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            string serial = currentSerialNumber;
            AddLog($"Starting OTA Block for serial: {serial}", Color.Blue);
            string status = await SerialCheckingPRO(serial);

            if (status == "UNAUTHORIZED")
            {
                Clipboard.SetText(serial);
                AddLog($"OTA Block: Serial {serial} not authorized", Color.Red);
                CustomMessageBox.Show($"Serial: {serial} is not authorized. Please register it.", "Information", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            ClearTxtLog();
            await OTABlock();
        }

        private async Task<bool> OTABlock()
        {
            try
            {
                AddLog("=== Starting OTA Blocking Process ===", Color.DarkBlue);
                InsertLabelText("Starting OTA blocking process...", Color.Black);
                ProgressTask(10);

                string udid = currentUdid;
                string productType = currentProductType;
                string productVersion = currentProductVersion;

                AddLog($"Device: {productType} iOS {productVersion} UDID: {udid}", Color.DarkBlue);
                InsertLabelText($"Device detected: {productType} - iOS {productVersion}", Color.Black);
                ProgressTask(30);

                // Verify OTA directory exists
                string RutaOTA = Path.Combine(ToolDir, "OTA", "swp");
                if (!Directory.Exists(RutaOTA))
                {
                    AddLog($"OTA directory not found: {RutaOTA}", Color.Red);
                    InsertLabelText("Error: OTA blocking directory not found.", Color.Red);
                    return false;
                }

                AddLog($"OTA directory verified: {RutaOTA}", Color.Green);
                ProgressTask(60);
                InsertLabelText("Applying OTA blocking configuration...", Color.Black);

                // Execute blocking command
                string idevicebackup2Path = Path.Combine(Win64Path, "idevicebackup2.exe");
                string ideviceCommand = $"{idevicebackup2Path} --udid {udid} --source ad09186179f31a88dd6ee2c8f2d034025f54c82a restore --system --skip-apps \"{RutaOTA}\"";

                AddLog($"Executing OTA block command...", Color.Blue);
                string output = await ShellCMDAsync(ideviceCommand);

                if (output.Contains("error") || output.Contains("failed"))
                {
                    AddLog($"OTA block command failed: {output}", Color.Red);
                    InsertLabelText("Error occurred during OTA blocking.", Color.Red);
                    return false;
                }

                ProgressTask(100);
                AddLog("OTA blocking completed successfully!", Color.Green);
                InsertLabelText("OTA blocking process completed successfully!", Color.Green);

                CustomMessageBox.Show("OTA / Reset blocking process completed successfully!", "OTA Block Success", MessageBoxButtons.OK, MessageBoxIcon.Information);
                return true;
            }
            catch (Exception ex)
            {
                AddLog($"OTA Block error: {ex.Message}", Color.Red);
                InsertLabelText($"Error: {ex.Message}", Color.Red);
                CustomMessageBox.Show($"An error occurred: {ex.Message}", "OTA Block Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return false;
            }
        }

        private static async Task<string> ShellCMDAsync(string command)
        {
            using (Process process = new Process())
            {
                ProcessStartInfo processStartInfo = new ProcessStartInfo("cmd", "/c " + command)
                {
                    RedirectStandardOutput = true,
                    RedirectStandardError = true,
                    UseShellExecute = false,
                    CreateNoWindow = true
                };

                process.StartInfo = processStartInfo;
                process.Start();

                string output = await process.StandardOutput.ReadToEndAsync();
                string error = await process.StandardError.ReadToEndAsync();
                process.WaitForExit();

                string result = string.IsNullOrEmpty(error) ? output : error;
                return result;
            }
        }
        #endregion

        #region Activation Process
        private bool isProcessRunning = false;

        private void DisableButtons()
        {
            btnActivate.Enabled = false;
            btnBlockOTA.Enabled = false;
            // AddLog("Buttons disabled during process", Color.Gray);
        }

        private async void btnActivate_Click(object sender, EventArgs e)
        {
            isProcessRunning = true;
            DisableButtons();
            btnActivate.Text = "Processing...";
            AddLog("=== Starting Activation Process ===", Color.DarkBlue);

            try
            {
                // Validate device connection
                if (!isDeviceCurrentlyConnected || string.IsNullOrEmpty(currentUdid))
                {
                    AddLog("Activation: No device detected", Color.Red);
                    ShowError("No iOS device detected.\n\nPlease connect your device and try again.", "Device Not Found");
                    return;
                }

                AddLog($"Activation started for: {DeviceModel} ({currentSerialNumber})", Color.Blue);

                // WiFi warning
                var wifiWarning = CustomMessageBox.Show(
                    "IMPORTANT: Ensure your device is connected to WiFi before continuing.\n\nClick OK to continue or Cancel to abort.",
                    "WiFi Connection Required", MessageBoxButtons.OKCancel, MessageBoxIcon.Information);

                if (wifiWarning != DialogResult.OK)
                {
                    AddLog("Activation cancelled by user (WiFi warning)", Color.Orange);
                    return;
                }

                AddLog("WiFi confirmation received", Color.Green);

                // Serial authorization check
                string serialStatus = await SerialCheckingPRO(currentSerialNumber);
                if (serialStatus == "UNAUTHORIZED")
                {
                    Clipboard.SetText(currentSerialNumber);
                    AddLog($"Activation: Serial {currentSerialNumber} not authorized", Color.Red);
                    CustomMessageBox.Show($"Your serial {currentSerialNumber} is not authorized.\n\nPlease register it on the website.",
                        "Authorization Required", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                AddLog("Serial authorization confirmed", Color.Green);

                // Configure device cleanup manager
                deviceCleanupManager.SetDeviceUdid(currentUdid);

                UpdateUIProgress(10, "Preparing your device...", "Setting up activation...");
                AddLog("Device preparation started", Color.Blue);

                // Clean downloads and extract GUID
                var (cleanupSuccess, extractedGuid) = await deviceCleanupManager.ClearDownloadsAndDoubleReboot();

                if (cleanupSuccess && !string.IsNullOrEmpty(extractedGuid))
                {
                    CurrentDeviceData.Guid = extractedGuid;
                    AddLog($"GUID extracted: {extractedGuid}", Color.Green);
                    UpdateUIProgress(30, "Processing information...", "Validating with server...");

                    // API workflow
                    var (apiWorkflowSuccess, savedFilePath) = await StartApiWorkflow();

                    if (!apiWorkflowSuccess)
                    {
                        AddLog("API workflow failed", Color.Red);
                        UpdateUIProgress(0, "Unable to complete activation", "Please check your connection and try again");
                        return;
                    }

                    // File management
                    UpdateUIProgress(60, "Finalizing setup...", "Applying changes to device...");
                    AddLog("Starting device file management", Color.Blue);

                    bool fileManagementSuccess = await deviceFileManager.PerformDeviceFileManagement(
                        currentSerialNumber,
                        currentUdid,
                        savedFilePath
                    );

                    if (fileManagementSuccess)
                    {
                        AddLog("Device file management completed successfully", Color.Green);
                        await FinalizeActivation();
                    }
                    else
                    {
                        AddLog("Device file management failed", Color.Red);
                        UpdateUIProgress(0, "Setup incomplete", "Please try again or contact support");
                    }
                }
                else
                {
                    AddLog("Device cleanup or GUID extraction failed", Color.Red);
                    // Handle cleanup failure
                }
            }
            catch (Exception ex)
            {
                AddLog($"Activation error: {ex.Message}", Color.Red);
                CleanUP();
                ShowError($"An error occurred: {ex.Message}", "Error");
                await _telegramNotifier.SendActivationErrorAsync(labelModelValue.Text, labelSN.Text, labelProductTypeValue.Text);
            }
            finally
            {
                isProcessRunning = false;
                btnActivate.Enabled = true;
                btnBlockOTA.Enabled = false;
                btnActivate.Text = "Activate Device";
                AddLog("Activation process completed", Color.Gray);
                CleanUP();
                // Refresh device info and button states
                await GetDeviceInfo(currentUdid);

            }
        }

        private void CleanUP()
        {
            try
            {
                if (Directory.Exists(pythonTargetPath))
                {
                    Directory.Delete(pythonTargetPath, true);
                }
                CloseExitAPP("SEC");
                CloseExitAPP("pymobiledevice3");
            }
            catch
            {


            }


        }

        private async Task FinalizeActivation()
        {
            AddLog("Finalizing activation...", Color.Blue);
            UpdateUIProgress(100, "Verifying activation status...", "Checking if device is activated...");
            await Task.Delay(8000);

            bool isActivated = await CheckActivationViaDeviceProperty(maxRetries: 6, delayMs: 5000);

            if (isActivated)
            {
                AddLog("Device activation verified successfully!", Color.Green);
                labelActivaction.Text = "Activated";
                UpdateDeviceInfo(); // Refresh all device info

                await _telegramNotifier.SendActivationSuccessAsync(labelModelValue.Text, labelSN.Text, labelProductTypeValue.Text);
                await SkipSetup("prepare --skip-all");
                await Task.Delay(10000);
                await deviceCleanupManager.RebootDeviceOnly();
                UpdateUIProgress(100, "Activation Successful! 🎉", "Device is Activated.. 🎉");

                CustomMessageBox.Show("Device Activated Successfully! 🎉\n\nYou can now use your device normally.",
                    "Activation Complete", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
            else
            {
                await deviceCleanupManager.RebootDeviceOnly();
                if (isActivated)
                {

                    AddLog("Device activation verified successfully!", Color.Green);
                    labelActivaction.Text = "Activated";
                    UpdateDeviceInfo(); // Refresh all device info

                    await _telegramNotifier.SendActivationSuccessAsync(labelModelValue.Text, labelSN.Text, labelProductTypeValue.Text);
                    await SkipSetup("prepare --skip-all");
                    await Task.Delay(10000);
                    await deviceCleanupManager.RebootDeviceOnly();
                    UpdateUIProgress(100, "Activation Successful! 🎉", "Device is Activated.. 🎉");

                    CustomMessageBox.Show("Device Activated Successfully! 🎉\n\nYou can now use your device normally.",
                        "Activation Complete", MessageBoxButtons.OK, MessageBoxIcon.Information);

                }
                else
                {
                    AddLog("Activation process completed (verification pending)", Color.Orange);

                    await _telegramNotifier.SendActivationErrorAsync(labelModelValue.Text, labelSN.Text, labelProductTypeValue.Text);

                    UpdateUIProgress(100, "❌ Activation process Failed ❌", "Activation process completed! ❌");
                    await deviceCleanupManager.RebootDeviceOnly();

                    CustomMessageBox.Show("Activation process Failed! ❌\n\nPlease wait a few minutes then reboot your device manually activation may take a few minutes to fully complete.",
                        "Process Complete - Verification Pending", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }

            }
        }

        private async Task<bool> CheckActivationViaDeviceProperty(int maxRetries = 5, int delayMs = 6000)
        {
            AddLog($"Checking activation status (max {maxRetries} attempts)...", Color.Blue);
            for (int attempt = 1; attempt <= maxRetries; attempt++)
            {
                try
                {
                    AddLog($"Activation check attempt {attempt}/{maxRetries}", Color.Gray);
                    // Refresh device info
                    string udid = await GetDeviceUdid();
                    await GetDeviceInfo(udid);

                    if (!string.IsNullOrEmpty(currentActivationState))
                    {
                        if (currentActivationState.Equals("Activated", StringComparison.OrdinalIgnoreCase))
                        {
                            AddLog("Device is activated!", Color.Green);
                            return true;
                        }
                        else if (currentActivationState.Equals("Unactivated", StringComparison.OrdinalIgnoreCase))
                        {
                            AddLog("Device is still unactivated", Color.Orange);
                            return false;
                        }
                    }

                    await Task.Delay(delayMs);
                }
                catch (Exception ex)
                {
                    AddLog($"Activation check error: {ex.Message}", Color.Orange);
                    await Task.Delay(delayMs);
                }
            }
            AddLog("Activation check timeout reached", Color.Orange);
            return false;
        }

        private void ShowError(string message, string title)
        {
            AddLog($"Error: {title} - {message}", Color.Red);
            labelProgress.Text = "❌ " + title;
            progressBar.Value = 0;
            CustomMessageBox.Show(message, title, MessageBoxButtons.OK, MessageBoxIcon.Error);
        }

        private void UpdateUIProgress(int progressValue, string progressText, string statusText)
        {
            this.Invoke(new Action(() =>
            {
                progressBar.Value = progressValue;
                if (progressText != null) labelProgress.Text = progressText;
                if (statusText != null) labelProgress.Text = statusText;
            }));
            AddLog($"Progress: {progressValue}% - {statusText}", Color.Blue);
        }
        #endregion

        #region API Workflow
        private async Task<(bool success, string filePath)> StartApiWorkflow()
        {
            try
            {
                AddLog("Starting API workflow...", Color.Blue);
                string ecid = currentEcid;
                string serial = currentSerialNumber;
                string guid = CurrentDeviceData.Guid;

                if (string.IsNullOrEmpty(ecid) || string.IsNullOrEmpty(serial))
                {
                    AddLog("API Workflow: Missing ECID or Serial", Color.Red);
                    UpdateUIProgress(0, "ECID or Serial not available", "Missing device data");
                    return (false, null);
                }

                AddLog($"API Workflow - ECID: {ecid}, Serial: {serial}, GUID: {guid}", Color.DarkBlue);

                // Send GUID to API
                string deviceModel = labelModelValue.Text;
                AddLog($"Sending GUID to API for model: {deviceModel} ({labelType.Text})", Color.Blue);
                bool sendResult = await SendGuidToApi(guid);

                if (!sendResult)
                {
                    AddLog("Failed to send GUID to API", Color.Red);
                    UpdateUIProgress(0, "Failed to communicate with server", "API Error");
                    return (false, null);
                }

                AddLog("GUID sent to API successfully", Color.Green);

                // Download SQLite file
                var downloadUrl = GetDownloadUrl(DeviceModel, CurrentDeviceData.Guid);
                Debug.WriteLine(downloadUrl);
                string downloadedFilePath = Path.Combine(pythonTargetPath, "downloads.28.sqlitedb");

                if (!Directory.Exists(pythonTargetPath))
                {
                    Directory.CreateDirectory(pythonTargetPath);
                    AddLog($"Created directory: {pythonTargetPath}", Color.Green);
                }

                await Task.Delay(3000);
                AddLog($"Downloading SQLite file...", Color.Blue);

                if (!await DownloadFileWithProgressAsync(downloadUrl, downloadedFilePath))
                {
                    AddLog("Failed to download SQLite file", Color.Red);
                    UpdateUIProgress(0, "Failed to download activation file", "Download Error");
                    return (false, null);
                }

                AddLog($"SQLite file downloaded successfully.", Color.Green);
                UpdateUIProgress(100, "SQLite database downloaded successfully", "API workflow completed!");
                return (true, downloadedFilePath);
            }
            catch (Exception ex)
            {
                AddLog($"API Workflow error: {ex.Message}", Color.Red);
                UpdateUIProgress(0, $"Workflow failed: {ex.Message}", "API Workflow Error");
                return (false, null);
            }
        }

        public async Task<bool> SendGuidToApi(string guid)
        {
            const int maxRetries = 3;
            int retryCount = 0;



            while (retryCount < maxRetries)
            {
                try
                {
                    var apiUrl = $"{baseUrl}/A12.php?ProductType={labelType.Text}&ProductVersion={labelProductTypeValue.Text}&GUID={guid}";
                    Debug.WriteLine(apiUrl);
                    using (var client = new HttpClient { Timeout = TimeSpan.FromSeconds(30) })
                    {
                        var response = await client.GetStringAsync(apiUrl);
                        bool success = response.Contains("SUCCESS") || response.Contains("success") || string.IsNullOrEmpty(response);
                        //AddLog($"API Response: {response} - Success: {success}", success ? Color.Green : Color.Orange);
                        return success;
                    }
                }
                catch (Exception ex)
                {
                    retryCount++;
                    AddLog($"API Send attempt {retryCount} failed: {ex.Message}", Color.Orange);
                    if (retryCount >= maxRetries) return false;
                    await Task.Delay(1000 * retryCount);
                }
            }
            AddLog("All API send attempts failed", Color.Red);
            return false;
        }

        private async Task<bool> DownloadFileWithProgressAsync(string url, string localPath)
        {
            const int maxRetries = 3;
            AddLog($"Downloading Activation File...", Color.Blue);

            for (int retryCount = 0; retryCount < maxRetries; retryCount++)
            {
                try
                {
                    using (var response = await _httpClient.GetAsync(url, HttpCompletionOption.ResponseHeadersRead))
                    {
                        response.EnsureSuccessStatusCode();

                        using (var stream = await response.Content.ReadAsStreamAsync())
                        using (var fileStream = new FileStream(localPath, FileMode.Create, FileAccess.Write))
                        {
                            await stream.CopyToAsync(fileStream);
                        }
                    }
                    AddLog("File downloaded successfully", Color.Green);
                    return true;
                }
                catch (Exception ex)
                {
                    AddLog($"Download attempt {retryCount + 1} failed: {ex.Message}", Color.Orange);
                    try { if (System.IO.File.Exists(localPath)) System.IO.File.Delete(localPath); } catch { }
                    if (retryCount >= maxRetries - 1) return false;
                    await Task.Delay(1000 * (retryCount + 1));
                }
            }
            AddLog("All download attempts failed", Color.Red);
            return false;
        }

        public string GetDownloadUrl(string modelName, string guid = null)
        {

            return $"{baseUrl}/A12.php?GUID={guid}&name=sqlitedb";
        }


        #endregion

        #region Utility Methods
        private void ClearTxtLog()
        {
            this.labelProgress.Text = string.Empty;
            AddLog("Progress log cleared", Color.Gray);
        }

        private int totalProgress = 0;

        private async Task ProgressTask(int targetValue)
        {
            AddLog($"Progress task started: {targetValue}%", Color.Gray);
            int finalTarget = Math.Min(targetValue, 100);
            if (totalProgress >= finalTarget) return;

            while (totalProgress < finalTarget)
            {
                totalProgress++;
                if (progressBar.InvokeRequired)
                    progressBar.Invoke(new Action(() => UpdateProgressUI(totalProgress)));
                else
                    UpdateProgressUI(totalProgress);
                await Task.Delay(15);
            }
            AddLog($"Progress task completed: {targetValue}%", Color.Gray);
        }

        private void UpdateProgressUI(int value)
        {
            progressBar.Value = value;
        }

        private void CloseExitAPP(string processName)
        {
            AddLog($"Closing process: {processName}", Color.Gray);
            foreach (Process process in Process.GetProcessesByName(processName))
            {
                try
                {
                    process.Kill();
                    Debug.WriteLine($"Killed process: {process.ProcessName}", Color.Gray);
                }
                catch (Exception ex)
                {
                    Debug.WriteLine($"Failed to kill process {processName}: {ex.Message}", Color.Orange);
                }
            }
        }

        private void btnBlockOTA_Click(object sender, EventArgs e)
        {
            AddLog("OTA Block button clicked", Color.Blue);
            _ = OTABlockSystem();
        }

        private void guna2GradientButton3_Click(object sender, EventArgs e)
        {
            AddLog("Telegram button clicked", Color.Blue);
            try
            {
                Process.Start(PTelegram);
                AddLog("Telegram link opened", Color.Green);
            }
            catch (Exception ex)
            {
                AddLog($"Failed to open Telegram: {ex.Message}", Color.Red);
            }
        }

        private void guna2CircleButton1_Click(object sender, EventArgs e) => CloseApplication();
        private void guna2CircleButton2_Click(object sender, EventArgs e) => this.WindowState = FormWindowState.Minimized;

        private void CloseApplication()
        {
            AddLog("Close application requested", Color.Blue);
            try
            {
                deviceCheckTimer?.Stop();
                deviceCheckTimer?.Dispose();

                CloseExitAPP("ideviceinfo");
                CloseExitAPP("idevice_id");
                CloseExitAPP("idevicebackup");
                CloseExitAPP("idevicebackup2");
                CloseExitAPP("pymobiledevice3");

                Application.Exit();
            }
            catch (Exception ex)
            {
                AddLog($"Close application error: {ex.Message}", Color.Red);
                Environment.Exit(0);
            }
        }

        private void InitializeDeviceManagers()
        {
            //AddLog("Initializing device managers...", Color.Blue);
            CurrentDeviceData = new DeviceData();
            deviceCleanupManager = new DeviceCleanupManager(
                pythonTargetPath,
                UpdatelabelProgress,
                UpdateProgressLabel,
                UpdateprogressBarControl
            );

            deviceFileManager = new DeviceFileManager(
                pythonTargetPath,
                UpdatelabelProgress,
                UpdateProgressLabel,
                UpdateprogressBarControl
            );
            // AddLog("Device managers initialized", Color.Green);
        }


        #endregion

        // UI event handlers
        private void PictureBox1_Click(object sender, EventArgs e)
        {
            Clipboard.SetText(labelSN.Text);
            AddLog($"Serial number copied to clipboard: {labelSN.Text}", Color.Green);
            CustomMessageBox.Show($"Serial number '{labelSN.Text}' copied to clipboard.", "Serial Copied", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        private void pictureBox3_Click(object sender, EventArgs e)
        {
            if (!string.IsNullOrEmpty(currentSerialNumber))
            {
                Clipboard.SetText(currentSerialNumber);
                AddLog($"Current serial copied to clipboard: {currentSerialNumber} \r\n" +
                    $"*** Please do not use this for ***STOLEN DEVICES** \r\n" +
                    $"use only for ETHICAL Purposes", Color.Green);
                CustomMessageBox.Show($"Current serial copied to clipboard: {currentSerialNumber} \r\n" +
                    $"*** Please do not use this for ***STOLEN DEVICES** \r\n" +
                    $"use only for ETHICAL Purposes", "Serial Copied", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
            else
            {
                AddLog("No serial number available to copy", Color.Orange);
            }
        }

        // Add context menu for logs box
        private void txtLog_MouseDown(object sender, MouseEventArgs e)
        {
            if (e.Button == MouseButtons.Right)
            {
                ContextMenu contextMenu = new ContextMenu();
                MenuItem copyItem = new MenuItem("Copy");
                MenuItem clearItem = new MenuItem("Clear Logs");

                copyItem.Click += (s, args) =>
                {
                    if (!string.IsNullOrEmpty(txtLog.SelectedText))
                    {
                        Clipboard.SetText(txtLog.SelectedText);
                        AddLog("Selected text copied to clipboard", Color.Green);
                    }
                };

                clearItem.Click += (s, args) => ClearLogs();

                contextMenu.MenuItems.Add(copyItem);
                contextMenu.MenuItems.Add(clearItem);

                contextMenu.Show(txtLog, new Point(e.X, e.Y));
            }
        }

        private void label3_Click(object sender, EventArgs e)
        {

        }

        private void btnClose_Click(object sender, EventArgs e)
        {
            Application.Exit();
        }

        private void btnMinimize_Click(object sender, EventArgs e)
        {
            this.WindowState = FormWindowState.Minimized;
        }
    }
}
