using Claunia.PropertyList;
using Microsoft.VisualBasic.Logging;
using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Net.Http;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading.Tasks;
using System.Web.UI.WebControls.WebParts;
using System.Windows.Forms;

namespace iSkorpionA12
{
    /// <summary>
    /// Manages device cleanup operations using pymobiledevice3
    /// Handles Downloads folder clearing, device reboot, and syslog collection
    /// </summary>
    public class DeviceCleanupManager
    {
        #region Private Fields
      
        private readonly Action<string> statusUpdateCallback;
        private readonly Action<string> progressUpdateCallback;
        private readonly Action<int> progressBarUpdateCallback;
        private string deviceUdid;
        private readonly string refDirectory;
        private static readonly string pythonTargetPath = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "iSkorpion", "python"
        );
        public DeviceData CurrentDeviceData { get; private set; }
        #endregion


        #region Constructor
        public DeviceCleanupManager(string pythonPath, Action<string> statusCallback, Action<string> progressCallback, Action<int> progressBarCallback)
        {
            Console.WriteLine("═══════════════════════════════════════════════════════════");
            Console.WriteLine("[INIT] Initializing DeviceCleanupManager");
            Console.WriteLine($"[INIT] Python path: {pythonPath}");

          
            statusUpdateCallback = statusCallback;
            progressUpdateCallback = progressCallback;
            progressBarUpdateCallback = progressBarCallback;

            CurrentDeviceData = new DeviceData();

            string exeDirectory = AppDomain.CurrentDomain.BaseDirectory;
            Console.WriteLine($"[INIT] EXE directory: {exeDirectory}");

            refDirectory = pythonTargetPath;
            Console.WriteLine($"[INIT] Ref directory: {refDirectory}");

            if (!Directory.Exists(refDirectory))
            {
                Console.WriteLine($"[INIT] Creating ref directory...");
                Directory.CreateDirectory(refDirectory);
                Console.WriteLine($"[INIT]   Ref directory created");
            }
            else
            {
                Console.WriteLine($"[INIT]   Ref directory already exists");
            }

            Console.WriteLine("[INIT]   DeviceCleanupManager initialized successfully");
            Console.WriteLine("═══════════════════════════════════════════════════════════");
        }
        #endregion

        #region Public Methods

        /// <summary>
        /// Cleans up all old files before starting the process
        /// </summary>
        private void CleanupOldFiles()
        {
            try
            {
                Console.WriteLine("═══════════════════════════════════════════════════════════");
                Console.WriteLine("[CLEANUP OLD FILES] Starting cleanup of old files...");

                // 1. Clean old JSONL files in Utils directory
                string utilsDirectory = pythonTargetPath;
                if (Directory.Exists(utilsDirectory))
                {
                    Console.WriteLine($"[CLEANUP OLD FILES] Checking Utils directory: {utilsDirectory}");
                    string[] jsonlFiles = Directory.GetFiles(utilsDirectory, "*.jsonl", SearchOption.TopDirectoryOnly);

                    if (jsonlFiles.Length > 0)
                    {
                        Console.WriteLine($"[CLEANUP OLD FILES] Found {jsonlFiles.Length} old JSONL file(s)");
                        foreach (string jsonlFile in jsonlFiles)
                        {
                            try
                            {
                                File.Delete(jsonlFile);
                                Console.WriteLine($"[CLEANUP OLD FILES]   ✓ Deleted: {Path.GetFileName(jsonlFile)}");
                            }
                            catch (Exception ex)
                            {
                                Console.WriteLine($"[CLEANUP OLD FILES]   ✗ Failed to delete {Path.GetFileName(jsonlFile)}: {ex.Message}");
                            }
                        }
                    }
                    else
                    {
                        Console.WriteLine($"[CLEANUP OLD FILES] No old JSONL files found in Utils");
                    }
                }

                // 2. Clean old logarchive folders in ref directory
                if (Directory.Exists(refDirectory))
                {
                    Console.WriteLine($"[CLEANUP OLD FILES] Checking ref directory: {refDirectory}");
                    string[] logarchiveFolders = Directory.GetDirectories(refDirectory, "*.logarchive", SearchOption.TopDirectoryOnly);

                    if (logarchiveFolders.Length > 0)
                    {
                        Console.WriteLine($"[CLEANUP OLD FILES] Found {logarchiveFolders.Length} old logarchive folder(s)");
                        foreach (string folder in logarchiveFolders)
                        {
                            try
                            {
                                Directory.Delete(folder, true);
                                Console.WriteLine($"[CLEANUP OLD FILES]   ✓ Deleted: {Path.GetFileName(folder)}");
                            }
                            catch (Exception ex)
                            {
                                Console.WriteLine($"[CLEANUP OLD FILES]   ✗ Failed to delete {Path.GetFileName(folder)}: {ex.Message}");
                            }
                        }
                    }
                    else
                    {
                        Console.WriteLine($"[CLEANUP OLD FILES] No old logarchive folders found in ref");
                    }
                }

                Console.WriteLine("[CLEANUP OLD FILES]   Cleanup completed successfully");
                Console.WriteLine("═══════════════════════════════════════════════════════════");
            }
            catch (Exception ex)
            {
                Console.WriteLine($"[CLEANUP OLD FILES]   Error during cleanup: {ex.Message}");
                Console.WriteLine("═══════════════════════════════════════════════════════════");
            }
        }

        public void SetDeviceUdid(string udid)
        {
            Console.WriteLine($"[DEVICE] Setting device UDID: {udid}");
            deviceUdid = udid;
            CurrentDeviceData.Udid = udid;
            Console.WriteLine($"[DEVICE]   Device UDID set successfully");
        }

        private string GetTelegramChatId()
        {
            return "7933497127";
        }

        public async Task<(bool success, string guid)> ClearDownloadsAndDoubleReboot()
        {
            try
            {
                Console.WriteLine("═══════════════════════════════════════════════════════════");
                Console.WriteLine("[WORKFLOW] Starting ClearDownloadsAndDoubleReboot");
                Console.WriteLine($"[WORKFLOW] Timestamp: {DateTime.Now:yyyy-MM-dd HH:mm:ss.fff}");
                Console.WriteLine("═══════════════════════════════════════════════════════════");

                if (string.IsNullOrEmpty(deviceUdid))
                {
                    Console.WriteLine("[WORKFLOW]   ERROR: Device UDID not set");
                    UpdateLabelInfo("❌ Error: Device UDID not set");
                    UpdateProgress("Cannot proceed without device UDID");
                    return (false, null);
                }

             
                UpdateLabelInfo("🔄 Preparing environment...");
            

                // ✅ CRITICAL: Clean up all old files before starting
                Console.WriteLine("[WORKFLOW] Cleaning up old files before starting...");
                UpdateLabelInfo("🧹 Preparing environment...");
                UpdateProgress("Cleaning up old files...");
                CleanupOldFiles();
                await Task.Delay(1000);

                Console.WriteLine($"[WORKFLOW] Device UDID: {deviceUdid}");
                Console.WriteLine($"[WORKFLOW] Max attempts: 3");

                int maxAttempts = 3;

                for (int attempt = 1; attempt <= maxAttempts; attempt++)
                {
                    try
                    {
                        Console.WriteLine("");
                        Console.WriteLine($"╔════════════════════════════════════════════════════════╗");
                        Console.WriteLine($"║  ATTEMPT {attempt}/{maxAttempts}                                          ║");
                        Console.WriteLine($"╚════════════════════════════════════════════════════════╝");

                        // Clean up previous attempts BEFORE starting new one
                        if (attempt > 1)
                        {
                            Console.WriteLine($"[ATTEMPT {attempt}] Cleaning up previous attempts...");
                            for (int prevAttempt = 1; prevAttempt < attempt; prevAttempt++)
                            {
                                string prevLogarchivePath = Path.Combine(refDirectory, $"bldatabasemanager_logs_attempt{prevAttempt}.logarchive");
                                await CleanupLogarchiveFolder(prevLogarchivePath);
                            }
                        }

                        UpdateLabelInfo($"🔄 Attempt {attempt}/{maxAttempts} - Starting activation process");
                        UpdateProgress($"Preparing device for activation...");
                        UpdateProgressBar((attempt - 1) * 33);

                        // STEP 1: Clear Downloads (skip if doesn't exist)
                        Console.WriteLine($"[ATTEMPT {attempt}] STEP 1: Clearing Downloads folder (if exists)");
                        UpdateLabelInfo($"🧹 Clearing temporary files... (Attempt {attempt})");
                        UpdateProgress("Clearing temporary files...");

                        try
                        {
                            await ExecuteAfcCommandDir("rm", "/Downloads/");
                            UpdateProgress("Temporary files cleared");
                            Console.WriteLine($"[ATTEMPT {attempt}]   ✓ Downloads folder cleared");
                        }
                        catch (Exception afcEx)
                        {
                            // This should not happen anymore, but just in case
                            Console.WriteLine($"[ATTEMPT {attempt}]   ℹ️ Downloads folder doesn't exist - skipping");
                            UpdateProgress("No temporary files to clear");
                        }

                        Console.WriteLine($"[ATTEMPT {attempt}]   STEP 1 Complete");

                        await Task.Delay(2000);

                        // STEP 2: Reboot
                        Console.WriteLine($"[ATTEMPT {attempt}] STEP 2: Rebooting device");
                        UpdateLabelInfo($"🔄 Restarting device... (Attempt {attempt})");
                        await ExecuteDiagnosticsCommand("restart");
                        UpdateProgress("Device restarting, please wait...");
                        Console.WriteLine($"[ATTEMPT {attempt}]   STEP 2 Complete");

                        // STEP 3: Wait for reconnection
                        Console.WriteLine($"[ATTEMPT {attempt}] STEP 3: Waiting for device reconnection");
                        UpdateLabelInfo($"⏳ Waiting for device reconnection... (Attempt {attempt})");
                        await WaitForDeviceReconnection();
                        Console.WriteLine($"[ATTEMPT {attempt}]   STEP 3 Complete");

                        // STEP 4: Collect syslog
                        Console.WriteLine($"[ATTEMPT {attempt}] STEP 4: Collecting syslog");
                        UpdateLabelInfo($"📊 Collecting device information... (Attempt {attempt})");
                        UpdateProgress("Gathering activation data...");

                        string logarchiveFilename = $"bldatabasemanager_logs_attempt{attempt}.logarchive";
                        await ExecuteSyslogCommand("collect", logarchiveFilename);
                        UpdateProgress("Device information collected");
                        Console.WriteLine($"[ATTEMPT {attempt}]   STEP 4 Complete");

                        // STEP 5: Process tracev3
                        Console.WriteLine($"[ATTEMPT {attempt}] STEP 5: Processing tracev3");
                        UpdateLabelInfo($"🔍 Extracting activation key... (Attempt {attempt})");
                        UpdateProgress("Extracting activation key...");

                        string logarchivePath = Path.Combine(refDirectory, logarchiveFilename);
                        string tracev3Path = Path.Combine(logarchivePath, "logdata.LiveData.tracev3");

                        Console.WriteLine($"[ATTEMPT {attempt}] Logarchive path: {logarchivePath}");
                        Console.WriteLine($"[ATTEMPT {attempt}] Tracev3 path: {tracev3Path}");

                        if (!File.Exists(tracev3Path))
                        {
                            Console.WriteLine($"[ATTEMPT {attempt}]   ❌ tracev3 file not found at: {tracev3Path}");
                            UpdateLabelInfo($"⚠️ Device data not ready, retrying... (Attempt {attempt})");
                            UpdateProgress($"Device data not ready, retrying...");
                            await CleanupLogarchiveFolder(logarchivePath);
                            continue;
                        }

                        Console.WriteLine($"[ATTEMPT {attempt}]   ✓ tracev3 file found");
                        await Task.Delay(5000);

                        string extractedGuid = await ExecuteSqlite3Py(tracev3Path);

                        // ✅ CRITICAL: Verify GUID was extracted
                        if (string.IsNullOrEmpty(extractedGuid))
                        {
                            Console.WriteLine($"[ATTEMPT {attempt}]   ❌ No GUID extracted");
                            UpdateLabelInfo($"❌ Activation key not found (Attempt {attempt})");
                            UpdateProgress($"Activation key not found, retrying...");
                            await CleanupLogarchiveFolder(logarchivePath);

                            // If this was the last attempt, show error and stop
                            if (attempt >= maxAttempts)
                            {
                                Console.WriteLine("[WORKFLOW]   ❌❌❌ All 3 attempts failed - NO GUID");
                                UpdateLabelInfo("❌ Activation failed after 3 attempts");
                                UpdateProgress("Could not extract activation key");
                                UpdateProgressBar(0);
                                ShowErrorMessagePY();
                                return (false, null);
                            }

                            continue;
                        }

                        // ✅ SUCCESS - GUID extracted
                        Console.WriteLine($"[ATTEMPT {attempt}] ═══ GUID EXTRACTION SUCCESS ═══");
                        Console.WriteLine($"[ATTEMPT {attempt}] GUID: '{extractedGuid}'");

                        // Save to CurrentDeviceData
                        CurrentDeviceData.Guid = extractedGuid;
                        CurrentDeviceData.Udid = deviceUdid;
                        CurrentDeviceData.LastUpdated = DateTime.Now;

                        Console.WriteLine($"[ATTEMPT {attempt}]   GUID saved to CurrentDeviceData.Guid");
                        UpdateLabelInfo($"✅ Activation key found! (Attempt {attempt})");

                        // Send to Telegram
                        try
                        {
                            UpdateProgress("Sending device information...");
                            UpdateLabelInfo($"📤 Sending notification... (Attempt {attempt})");
                       
                            UpdateProgress("Device information sent");
                        }
                        catch (Exception telegramEx)
                        {
                            Console.WriteLine($"[ATTEMPT {attempt}] ⚠️ Telegram failed: {telegramEx.Message}");
                            UpdateProgress("⚠️ Notification failed (continuing)");
                        }

                        UpdateProgress("Activation successful");
                        UpdateProgressBar(100);
                        UpdateLabelInfo($"🎉 Activation completed successfully! GUID: {extractedGuid.Substring(0, 8)}...");

                        // Keep logarchive folder for reference
                        Console.WriteLine($"[ATTEMPT {attempt}] ✅ Keeping logarchive folder for reference: {logarchivePath}");

                        Console.WriteLine($"[ATTEMPT {attempt}] ═══════════════════════════════");
                        Console.WriteLine($"[ATTEMPT {attempt}] 🎉 SUCCESS! Returning GUID");
                        Console.WriteLine($"[ATTEMPT {attempt}] Final GUID: {extractedGuid}");
                        Console.WriteLine($"[ATTEMPT {attempt}] Logarchive saved at: {logarchivePath}");
                        Console.WriteLine($"[ATTEMPT {attempt}] ═══════════════════════════════");

                        return (true, CurrentDeviceData.Guid);
                    }
                    catch (Exception ex)
                    {
                        Console.WriteLine($"[ATTEMPT {attempt}]   ❌ EXCEPTION: {ex.Message}");
                        Console.WriteLine($"[ATTEMPT {attempt}] Stack trace: {ex.StackTrace}");
                        UpdateLabelInfo($"❌ Error on attempt {attempt}: {ex.Message}");
                        UpdateProgress($"Error on attempt {attempt}, retrying...");

                        string logarchivePath = Path.Combine(refDirectory, $"bldatabasemanager_logs_attempt{attempt}.logarchive");
                        await CleanupLogarchiveFolder(logarchivePath);

                        // If this was the last attempt, show error
                        if (attempt >= maxAttempts)
                        {
                            Console.WriteLine("[WORKFLOW]   ❌❌❌ All 3 attempts failed - EXCEPTION");
                            UpdateLabelInfo("❌ Activation failed after 3 attempts");
                            UpdateProgress("Could not activate device");
                            UpdateProgressBar(0);
                            ShowErrorMessagePY();
                            return (false, null);
                        }
                    }

                    // Prepare for next attempt
                    if (attempt < maxAttempts)
                    {
                        Console.WriteLine($"[ATTEMPT {attempt}] ⏳ Preparing for next attempt...");
                        UpdateLabelInfo($"⏳ Preparing next attempt... ({attempt + 1}/{maxAttempts})");
                        UpdateProgress($"⏳ Attempt {attempt} failed, trying again...");
                        await Task.Delay(3000);
                    }
                }

                // Should never reach here, but just in case
                Console.WriteLine("[WORKFLOW]   ❌ All 3 attempts failed");
                UpdateLabelInfo("❌ Activation failed after 3 attempts");
                UpdateProgress("Could not activate device");
                UpdateProgressBar(0);
                ShowErrorMessagePY();
                return (false, null);
            }
            catch (Exception ex)
            {
                Console.WriteLine("[WORKFLOW]   ❌ FATAL ERROR");
                Console.WriteLine($"[WORKFLOW] Error: {ex.Message}");
                Console.WriteLine($"[WORKFLOW] Stack trace: {ex.StackTrace}");

                UpdateLabelInfo($"❌ Fatal error: {ex.Message}");
                UpdateProgress($"An error occurred during activation");
                UpdateProgressBar(0);

                ShowErrorMessagePY();

                return (false, null);
            }
        }


        public async Task<bool> CollectSyslogAfterReboot()
        {
            try
            {
                Console.WriteLine("[SYSLOG] Starting collection");

                if (string.IsNullOrEmpty(deviceUdid))
                {
                    Console.WriteLine("[SYSLOG]   No UDID");
                    UpdateLabelInfo("❌ Error: Device UDID not set");
                    return false;
                }

                UpdateLabelInfo("📊 Collecting syslog...");
                UpdateProgressBar(20);

                await Task.Delay(3000);
                await ExecuteSyslogCommand("collect", "bldatabasemanager_logs.logarchive");

                UpdateProgressBar(100);
                UpdateLabelInfo("✅ Syslog collection completed");
                Console.WriteLine("[SYSLOG]   Complete");

                return true;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"[SYSLOG]   {ex.Message}");
                UpdateLabelInfo($"❌ Syslog error: {ex.Message}");
                return false;
            }
        }

        public async Task<bool> ClearDownloadsOnly()
        {
            try
            {
                Console.WriteLine("[CLEAR DOWNLOADS] Starting");
                UpdateLabelInfo("🧹 Clearing Downloads folder...");
                UpdateProgressBar(10);

                await ExecuteAfcCommandDir("rm", "/Downloads/");

                UpdateProgressBar(100);
                UpdateLabelInfo("✅ Downloads cleared");
                Console.WriteLine("[CLEAR DOWNLOADS]   Complete");
                return true;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"[CLEAR DOWNLOADS]   {ex.Message}");
                UpdateLabelInfo($"❌ Error: {ex.Message}");
                return false;
            }
        }

        public async Task<bool> RebootDeviceOnly()
        {
            try
            {
                Console.WriteLine("[REBOOT] Starting");
                UpdateLabelInfo("🔄 Rebooting device...");
                UpdateProgressBar(50);

                await ExecuteDiagnosticsCommand("restart");

                UpdateProgressBar(100);
                UpdateLabelInfo("✅ Device rebooted");
                await Task.Delay(5000);
                Console.WriteLine("[REBOOT]   Complete");
                return true;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"[REBOOT]   {ex.Message}");
                UpdateLabelInfo($"❌ Reboot error: {ex.Message}");
                return false;
            }
        }

        public async Task<bool> ShutdownDevice()
        {
            try
            {
                Console.WriteLine("[SHUTDOWN] Starting");
                UpdateLabelInfo("⚡ Shutting down device...");
                UpdateProgressBar(50);

                await ExecuteDiagnosticsCommand("shutdown");

                UpdateProgressBar(100);
                UpdateLabelInfo("✅ Device shut down");
                Console.WriteLine("[SHUTDOWN]   Complete");
                return true;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"[SHUTDOWN]   {ex.Message}");
                UpdateLabelInfo($"❌ Shutdown error: {ex.Message}");
                return false;
            }
        }
        #endregion

        #region Private Methods

        private async Task ExecuteAfcCommand(string command, string path)
        {
            await Task.Run(() =>
            {
                try
                {
                    Console.WriteLine($"[AFC] Command: {command} {path}");

                    string pythonExe = Environment.CurrentDirectory + "\\win-x64\\afcclient.exe";
                    

                    var psi = new ProcessStartInfo
                    {
                        FileName = pythonExe,
                        Arguments = $"{command} --udid {deviceUdid} \"{path}\"",
                        UseShellExecute = false,
                        RedirectStandardOutput = true,
                        RedirectStandardError = true,
                        CreateNoWindow = true,
                        WindowStyle = ProcessWindowStyle.Hidden,
                       
                    };

        
                    psi.EnvironmentVariables["PYTHONIOENCODING"] = "utf-8";

                    using (var process = new Process { StartInfo = psi })
                    {
                        process.Start();
                        string output = process.StandardOutput.ReadToEnd();
                        string errors = process.StandardError.ReadToEnd();
                        process.WaitForExit();

                        Console.WriteLine($"[AFC] Exit code: {process.ExitCode}");

                        if (process.ExitCode != 0)
                        {
                            // ✅ If Downloads folder doesn't exist, that's OK - nothing to delete
                            if (errors.Contains("OBJECT_NOT_FOUND") || errors.Contains("AfcFileNotFoundError"))
                            {
                                Console.WriteLine($"[AFC]   ℹ️ Path not found: {path}");
                                Console.WriteLine($"[AFC]   ✓ Nothing to delete - continuing normally");
                                return; // Not an error - just means nothing exists to delete
                            }

                            // Other errors should still fail
                            Console.WriteLine($"[AFC]   ❌ Failed: {errors}");
                            throw new Exception($"AFC failed: {errors}");
                        }

                        Console.WriteLine("[AFC]   ✓ Success");
                    }
                }
                catch (Exception ex)
                {
                    Console.WriteLine($"[AFC]   Exception: {ex.Message}");
                    throw;
                }
            });
        }


        private Task WaitForExitAsync(Process process)
        {
            var tcs = new TaskCompletionSource<object>();
            process.EnableRaisingEvents = true;
            process.Exited += (sender, args) => tcs.TrySetResult(null);

            if (process.HasExited)
            {
                tcs.TrySetResult(null);
            }

            return tcs.Task;
        }

        private async Task<string> RunProcessWithTimeout(string fileName, string arguments, int timeoutMs)
        {
            try
            {
                var processStartInfo = new ProcessStartInfo
                {
                    FileName = Path.Combine(Environment.CurrentDirectory,"win-x64",fileName),
                    Arguments = arguments,
                    UseShellExecute = false,
                    RedirectStandardOutput = true,
                    RedirectStandardError = true,
                    CreateNoWindow = true
                };

                using (var process = new Process())
                {
                    process.StartInfo = processStartInfo;

                    var outputBuilder = new StringBuilder();
                    var errorBuilder = new StringBuilder();

                    process.OutputDataReceived += (sender, e) =>
                    {
                        if (e.Data != null)
                            outputBuilder.AppendLine(e.Data);
                    };

                    process.ErrorDataReceived += (sender, e) =>
                    {
                        if (e.Data != null)
                            errorBuilder.AppendLine(e.Data);
                    };

                    process.Start();
                    process.BeginOutputReadLine();
                    process.BeginErrorReadLine();

                    // Use Task.WhenAny to implement timeout
                    var processTask = WaitForExitAsync(process);
                    var timeoutTask = Task.Delay(timeoutMs);

                    var completedTask = await Task.WhenAny(processTask, timeoutTask);

                    if (completedTask == timeoutTask)
                    {
                        // Process timed out
                        try
                        {
                            if (!process.HasExited)
                            {
                                process.Kill();
                                await Task.Delay(100); // Give it a moment to terminate
                            }
                        }
                        catch (Exception killEx)
                        {
                            Debug.WriteLine($"⚠️ Error killing timed out process: {killEx.Message}");
                        }

                        throw new TimeoutException($"Process {fileName} timed out after {timeoutMs}ms");
                    }

                    var errorOutput = errorBuilder.ToString();
                    if (!string.IsNullOrEmpty(errorOutput) && !errorOutput.Contains("No device found"))
                    {
                        Debug.WriteLine($"Process error output: {errorOutput}");
                    }

                    return outputBuilder.ToString();
                }
            }
            catch (TimeoutException)
            {
                throw; // Re-throw timeout exceptions
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Process error: {ex.Message}");
                return $"ERROR: {ex.Message}";
            }
        }


      

        private async Task<bool> ExecuteAfcCommandDir(string command, string folderPath )
        {
            try
            {
                await ExecuteAfcCommand("rm", "Downloads/downloads.28.sqlitedb");
                await ExecuteAfcCommand("rm", "Downloads/downloads.28.sqlitedb-shm");
                await ExecuteAfcCommand("rm", "Downloads/downloads.28.sqlitedb-wal");

                try
                {
                    
                    await ExecuteAfcCommand("rm", "Books/asset3.epub");
                    await ExecuteAfcCommand("rm", "Books/asset.epub");
                    await ExecuteAfcCommand("rm", "Books/asset1.epub");
                }
                catch
                {

                }
                return true;
            } catch
            {
                return false;
            }
          
        }

        private async Task ExecuteDiagnosticsCommand(string command)
        {
            await Task.Run(() =>
            {
                try
                {
                    Console.WriteLine($"[DIAGNOSTICS] Command: {command}");

                    string pythonExe = Path.Combine(Environment.CurrentDirectory,"win-x64", "idevicediagnostics.exe");
            

                    var psi = new ProcessStartInfo
                    {
                        FileName = pythonExe,
                        Arguments = $"{command} --udid {deviceUdid}",
                        UseShellExecute = false,
                        RedirectStandardOutput = true,
                        RedirectStandardError = true,
                        CreateNoWindow = true,
                        WindowStyle = ProcessWindowStyle.Hidden,
                    
                    };

                

                    using (var process = new Process { StartInfo = psi })
                    {
                        process.Start();
                        string output = process.StandardOutput.ReadToEnd();
                        string errors = process.StandardError.ReadToEnd();
                        process.WaitForExit();

                        Console.WriteLine($"[DIAGNOSTICS] Exit code: {process.ExitCode}");

                       

                        Console.WriteLine("[DIAGNOSTICS]   Success");
                    }
                }
                catch (Exception ex)
                {
                    Console.WriteLine($"[DIAGNOSTICS]   Exception: {ex.Message}");
                    throw;
                }
            });
        }

        private async Task ExecuteSyslogCommand(string command, string outputFilename)
        {
            await Task.Run(() =>
            {
                try
                {
                    Console.WriteLine($"[SYSLOG CMD] Command: {command}");
                    Console.WriteLine($"[SYSLOG CMD] Filename: {outputFilename}");

                    string fullOutputPath = Path.Combine(refDirectory, outputFilename);
                    Console.WriteLine($"[SYSLOG CMD] Full path: {fullOutputPath}");

                    string pm3 = Environment.CurrentDirectory + "\\win-x64\\pymobiledevice3.exe";
                 

                    var psi = new ProcessStartInfo
                    {
                        FileName = pm3,
                        Arguments = $"syslog {command} --udid {deviceUdid} \"{fullOutputPath}\"",
                        UseShellExecute = false,
                        RedirectStandardOutput = true,
                        RedirectStandardError = true,
                        CreateNoWindow = true,
                        WindowStyle = ProcessWindowStyle.Hidden,
                       
                    };

                    
                    psi.EnvironmentVariables["PYTHONIOENCODING"] = "utf-8";

                    Console.WriteLine("[SYSLOG CMD] Starting process...");

                    using (var process = new Process { StartInfo = psi })
                    {
                        process.Start();
                        string output = process.StandardOutput.ReadToEnd();
                        string errors = process.StandardError.ReadToEnd();
                        process.WaitForExit();

                        Console.WriteLine($"[SYSLOG CMD] Exit code: {process.ExitCode}");

                        if (Directory.Exists(fullOutputPath))
                        {
                            Console.WriteLine($"[SYSLOG CMD]   Folder created: {fullOutputPath}");

                            string[] files = Directory.GetFiles(fullOutputPath, "*", SearchOption.AllDirectories);
                            Console.WriteLine($"[SYSLOG CMD] Files: {files.Length}");
                            foreach (string file in files.Take(5))
                            {
                                FileInfo fi = new FileInfo(file);
                                Console.WriteLine($"[SYSLOG CMD]   - {fi.Name} ({fi.Length} bytes)");
                            }
                        }

                        if (process.ExitCode != 0)
                        {
                            Console.WriteLine($"[SYSLOG CMD]   Failed");
                            throw new Exception($"Syslog failed: {errors}");
                        }

                        Console.WriteLine("[SYSLOG CMD]   Success");
                    }
                }
                catch (Exception ex)
                {
                    Console.WriteLine($"[SYSLOG CMD]   Exception: {ex.Message}");
                    throw;
                }
            });
        }

        private async Task<string> ExecuteSqlite3Py(string tracev3Path)
        {
            Console.WriteLine("═══════════════════════════════════════════════════════════");
            Console.WriteLine($"[GUID EXTRACTION] Starting extraction");
            Console.WriteLine($"[GUID EXTRACTION] Tracev3 file: {tracev3Path}");
            Console.WriteLine("═══════════════════════════════════════════════════════════");

            return await Task.Run(() =>
            {
                var stopwatch = Stopwatch.StartNew();
                string guid = TryExtractWithSecExe(tracev3Path);
                stopwatch.Stop();

                Console.WriteLine($"[GUID EXTRACTION] Completed in {stopwatch.ElapsedMilliseconds}ms");

                if (!string.IsNullOrEmpty(guid))
                {
                    Console.WriteLine($"[GUID EXTRACTION] ✅ Success: {guid}");
                    return guid;
                }

                Console.WriteLine("[GUID EXTRACTION] ❌ No GUID found");
                return null;
            });
        }

        public static string GenerateRandomString()
        {
            const string chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
            var random = new Random();
            return new string(Enumerable.Repeat(chars, 10)
                .Select(s => s[random.Next(s.Length)]).ToArray());
        }

        private string TryExtractWithSecExe(string tracev3Path)
        {
            try
            {
                Console.WriteLine("[SEC.EXE] Starting GUID extraction");

                string exeDirectory = AppDomain.CurrentDomain.BaseDirectory;
                string secExePath = Path.Combine(exeDirectory, "win-x64", "sec.exe");

                string serialNumber = GenerateRandomString();
                string outputJsonl = Path.Combine(pythonTargetPath, $"sec_{serialNumber}.jsonl");

                if (!File.Exists(secExePath))
                {
                    Console.WriteLine($"[SEC.EXE] ❌ Not found: {secExePath}");
                    return null;
                }

                Console.WriteLine($"[SEC.EXE] sec.exe path: {secExePath}");
                Console.WriteLine($"[SEC.EXE] Input: {tracev3Path}");
                Console.WriteLine($"[SEC.EXE] Output: {outputJsonl}");

                // Delete old output file if exists
                if (File.Exists(outputJsonl))
                {
                    try
                    {
                        File.Delete(outputJsonl);
                        Console.WriteLine($"[SEC.EXE] Deleted old output file");
                    }
                    catch (Exception ex)
                    {
                        Console.WriteLine($"[SEC.EXE] Warning: Could not delete old file: {ex.Message}");
                    }
                }

                var psi = new ProcessStartInfo
                {
                    FileName = secExePath,
                    Arguments = $"--mode single-file --input \"{tracev3Path}\" --output \"{outputJsonl}\" --format jsonl",
                    UseShellExecute = false,
                    RedirectStandardOutput = true,
                    RedirectStandardError = true,
                    CreateNoWindow = true,
                    WindowStyle = ProcessWindowStyle.Hidden,
                    WorkingDirectory = Path.GetDirectoryName(secExePath)
                };

                Console.WriteLine($"[SEC.EXE] Working directory: {psi.WorkingDirectory}");
                Console.WriteLine($"[SEC.EXE] Starting process...");

                var process = new Process { StartInfo = psi };

                var outputBuilder = new StringBuilder();
                var errorBuilder = new StringBuilder();

                process.OutputDataReceived += (sender, e) =>
                {
                    if (!string.IsNullOrEmpty(e.Data))
                    {
                        outputBuilder.AppendLine(e.Data);
                        Console.WriteLine($"[SEC.EXE] STDOUT: {e.Data}");
                    }
                };

                process.ErrorDataReceived += (sender, e) =>
                {
                    if (!string.IsNullOrEmpty(e.Data))
                    {
                        errorBuilder.AppendLine(e.Data);
                    }
                };

                var stopwatch = Stopwatch.StartNew();
                process.Start();

                process.BeginOutputReadLine();
                process.BeginErrorReadLine();

                Console.WriteLine($"[SEC.EXE] Process started (PID: {process.Id})");
                Console.WriteLine($"[SEC.EXE] Waiting for process to complete...");

                process.WaitForExit();

                stopwatch.Stop();
                Console.WriteLine($"[SEC.EXE] Process finished in {stopwatch.ElapsedMilliseconds}ms ({stopwatch.Elapsed.TotalSeconds:F2} seconds)");
                Console.WriteLine($"[SEC.EXE] Exit code: {process.ExitCode}");

                string output = outputBuilder.ToString();
                string errors = errorBuilder.ToString();

                if (!string.IsNullOrWhiteSpace(output))
                {
                    Console.WriteLine($"[SEC.EXE] Full STDOUT:\n{output}");
                }

                if (!string.IsNullOrWhiteSpace(errors))
                {
                    Console.WriteLine($"[SEC.EXE] Full STDERR:\n{errors}");
                }

                if (process.ExitCode != 0)
                {
                    Console.WriteLine($"[SEC.EXE] ❌ Process failed with exit code {process.ExitCode}");
                    return null;
                }

                System.Threading.Thread.Sleep(1000);

                if (!File.Exists(outputJsonl))
                {
                    Console.WriteLine($"[SEC.EXE] ❌ Output file not created at: {outputJsonl}");
                    return null;
                }

                FileInfo fileInfo = new FileInfo(outputJsonl);
                Console.WriteLine($"[SEC.EXE] Output file size: {fileInfo.Length:N0} bytes");

                if (fileInfo.Length == 0)
                {
                    Console.WriteLine($"[SEC.EXE] ❌ Output file is empty (0 bytes)");
                    return null;
                }

                Console.WriteLine($"[SEC.EXE] Parsing JSONL file...");

                return ParseJsonlFast(outputJsonl);
            }
            catch (Exception ex)
            {
                Console.WriteLine($"[SEC.EXE] ❌ Exception: {ex.Message}");
                Console.WriteLine($"[SEC.EXE] Stack trace: {ex.StackTrace}");
                return null;
            }
        }

        private string ParseJsonlFast(string jsonlPath)
        {
            var parseWatch = Stopwatch.StartNew();

            try
            {
                Console.WriteLine("[PARSE] Reading JSONL file");

                var guidScores = new Dictionary<string, int>();
                int processedLines = 0;
                int relevantLines = 0;

                using (var reader = new StreamReader(jsonlPath, Encoding.UTF8, true, 65536))
                {
                    string line;

                    while ((line = reader.ReadLine()) != null)
                    {
                        processedLines++;

                        if (!line.Contains("BLDatabaseManager.sqlite"))
                            continue;

                        relevantLines++;

                        try
                        {
                            int systemGroupIndex = line.IndexOf("SystemGroup/");
                            if (systemGroupIndex == -1) continue;

                            int guidStart = systemGroupIndex + 12;
                            int guidEnd = line.IndexOf('/', guidStart);

                            if (guidEnd == -1 || guidEnd - guidStart != 36) continue;

                            string guid = line.Substring(guidStart, 36).ToUpper();

                            if (guid.Length != 36 || guid[8] != '-' || guid[13] != '-' || guid[18] != '-' || guid[23] != '-')
                                continue;

                            if (!IsValidGuid(guid))
                                continue;

                            int score = 50;

                            if (line.Contains("file:///private/var/containers/Shared/SystemGroup/"))
                                score += 100;

                            if (line.Contains("/Documents/"))
                                score += 100;

                            if (guidScores.ContainsKey(guid))
                            {
                                guidScores[guid] += score + 10;
                            }
                            else
                            {
                                guidScores[guid] = score;
                            }

                            if (guidScores[guid] >= 250)
                            {
                                parseWatch.Stop();
                                Console.WriteLine($"[PARSE] Perfect candidate found at line {processedLines}");
                                Console.WriteLine($"[PARSE] Processed {processedLines} lines in {parseWatch.ElapsedMilliseconds}ms");
                                Console.WriteLine($"[PARSE] GUID: {guid} (Score: {guidScores[guid]})");
                                return guid;
                            }
                        }
                        catch
                        {
                            continue;
                        }
                    }
                }

                parseWatch.Stop();
                Console.WriteLine($"[PARSE] Processed {processedLines} lines ({relevantLines} relevant) in {parseWatch.ElapsedMilliseconds}ms");

                if (guidScores.Count > 0)
                {
                    var best = guidScores.OrderByDescending(x => x.Value).First();

                    Console.WriteLine("═══════════════════════════════════════════════════════════");
                    Console.WriteLine($"[PARSE] SELECTED GUID: {best.Key}");
                    Console.WriteLine($"[PARSE] Final Score: {best.Value}");
                    Console.WriteLine($"[PARSE] Total candidates: {guidScores.Count}");

                    var topCandidates = guidScores.OrderByDescending(x => x.Value).Take(3).ToList();
                    for (int i = 0; i < topCandidates.Count; i++)
                    {
                        Console.WriteLine($"[PARSE] #{i + 1}: {topCandidates[i].Key} (Score: {topCandidates[i].Value})");
                    }

                    Console.WriteLine("═══════════════════════════════════════════════════════════");

                    return best.Key;
                }

                Console.WriteLine("[PARSE] ❌ No valid GUID candidates found");
                return null;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"[PARSE] ❌ Exception: {ex.Message}");
                return null;
            }
        }

      
        public async Task WaitForDeviceReconnection()
        {
            Console.WriteLine("[RECONNECT] Waiting for device...");
            UpdateProgress("Monitoring device connection...");

            int maxWaitTime = 60;
            int waitInterval = 3;

            for (int i = 0; i < maxWaitTime; i += waitInterval)
            {
                await Task.Delay(waitInterval * 1000);
                Console.WriteLine($"[RECONNECT] Checking... ({i + waitInterval}s)");

                bool deviceDetected = await CheckDeviceConnection();

                if (deviceDetected)
                {
                    Console.WriteLine("[RECONNECT]   Device detected!");
                    UpdateLabelInfo("✅ Device reconnected!");
                    UpdateProgress("Device reconnection detected!");
                    return;
                }

                UpdateLabelInfo($"⏳ Waiting for device... ({i + waitInterval}s/{maxWaitTime}s)");
                UpdateProgress($"Waiting for device... ({i + waitInterval}s)");
            }

            Console.WriteLine("[RECONNECT]   Timeout - proceeding anyway");
            UpdateLabelInfo("⏳ Device reconnection timeout - proceeding anyway");
            UpdateProgress("Device reconnection timeout - proceeding anyway");
        }

        private async Task<bool> CheckDeviceConnection()
        {
            return await Task.Run(() =>
            {
                try
                {
                    Console.WriteLine("[CONNECTION CHECK] Checking...");

                    string pythonExe = Environment.CurrentDirectory + "\\win-x64\\idevice_id.exe";
                  

                    var psi = new ProcessStartInfo
                    {
                        FileName = pythonExe,
                        Arguments = "-l",
                        UseShellExecute = false,
                        RedirectStandardOutput = true,
                        RedirectStandardError = true,
                        CreateNoWindow = true,
                        WindowStyle = ProcessWindowStyle.Hidden,
                       
                    };

                    psi.EnvironmentVariables["PYTHONIOENCODING"] = "utf-8";

                    using (var process = new Process { StartInfo = psi })
                    {
                        process.Start();
                        string output = process.StandardOutput.ReadToEnd();
                        string errors = process.StandardError.ReadToEnd();
                        process.WaitForExit();

                        bool isConnected = process.ExitCode == 0 && !string.IsNullOrEmpty(output);
                        Console.WriteLine($"[CONNECTION CHECK] Connected: {isConnected}");

                        return isConnected;
                    }
                }
                catch (Exception ex)
                {
                    Console.WriteLine($"[CONNECTION CHECK]   {ex.Message}");
                    return false;
                }
            });
        }

        private async Task CleanupLogarchiveFolder(string logarchivePath)
        {
            try
            {
                Console.WriteLine($"[CLEANUP] Target: {logarchivePath}");
                UpdateProgress($"🧹 Cleaning up...");


                if (Directory.Exists(logarchivePath))
                {
                    try
                    {
                        Directory.Delete(logarchivePath, true);
                        Console.WriteLine($"[CLEANUP]   Deleted");
                    }
                    catch (Exception ex)
                    {
                        Console.WriteLine($"[CLEANUP]   Couldn't delete: {ex.Message}");

                        try
                        {
                            string[] files = Directory.GetFiles(logarchivePath, "*", SearchOption.AllDirectories);
                            foreach (string file in files)
                            {
                                try { File.Delete(file); } catch { }
                            }
                            Directory.Delete(logarchivePath, true);
                            Console.WriteLine($"[CLEANUP]   Force deleted");
                        }
                        catch
                        {
                            Console.WriteLine($"[CLEANUP]   Failed to delete");
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine($"[CLEANUP]   {ex.Message}");
            }
        }


        private bool IsValidGuid(string guidString)
        {
            if (string.IsNullOrWhiteSpace(guidString))
                return false;

            return Guid.TryParse(guidString, out _);
        }

        private void ShowErrorMessagePY()
        {
            MessageBox.Show(
                "Could not extract GUID after 3 attempts.\n\n" +
                "Please try the following:\n\n" +
                "• Restore your device using iTunes/Finder and try again\n" +
                "• Restart your device and try again\n" +
                "• Check USB connection\n" +
                "• Verify device is in normal mode\n" +
                "• Make sure iTunes is installed",
                "GUID Extraction Failed",
                MessageBoxButtons.OK,
                MessageBoxIcon.Warning
            );
        }


        private void UpdateProgress(string message)
        {
            progressUpdateCallback?.Invoke(message);
        }

        private void UpdateProgressBar(int value)
        {
            progressBarUpdateCallback?.Invoke(value);
        }

        private void UpdateLabelInfo(string message)
        {
            try
            {
                if (Form1.Instance != null && Form1.Instance.InvokeRequired)
                {
                    Form1.Instance.Invoke(new Action(() =>
                    {
                        Form1.Instance.labelInfoProgres.Text = message;
                    }));
                }
                else if (Form1.Instance != null)
                {
                    Form1.Instance.labelInfoProgres.Text = message;
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine($"[UPDATE LABEL] Error: {ex.Message}");
            }
        }

        #endregion
    }
}