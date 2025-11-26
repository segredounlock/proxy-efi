using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.Linq;
using System.Threading;
using System.Threading.Tasks;
using System.Windows;

namespace iSkorpionA12
{
    public class ProcessMonitor
    {
        private readonly List<string> _processesToKill;
        private readonly List<string> _processPatterns;
        private CancellationTokenSource _cancellationTokenSource;
        private Task _monitoringTask;
        private bool _isMonitoring;

        public event EventHandler<ProcessKilledEventArgs> ProcessKilled;
        //public event EventHandler<LogMessageEventArgs> LogMessageReceived;

        public ProcessMonitor()
        {
            // Specific processes to kill
            _processesToKill = new List<string>
{
    // .NET / .NET decompilers
    "dnspy",
    "dnspy-x86",
    "ilspy",
    "dotpeek",
    "justdecompile",
    "reflector",

    // HTTP / MITM proxies and sniffers
    "fiddler",
    "fiddler.exe",
    "charles",
    "charles.exe",
    "burp",
    "burpsuite",
    "burpsuite.exe",
    "mitmproxy",
    "mitmdump",
    "mitmproxy.exe",
    "proxifier",
    "mitmproxy.exe",

    // Packet capture / analysis
    "wireshark",
    "tshark",
    "tcpdump",
    "tcpdump.exe",
    "tcpflow",
    "ngrep",
    "ettercap",
    "ettercap.exe",
    "netmon",
    "netmon.exe",

    // Reverse engineering / disassemblers / analysis
    "ida",
    "idaq",
    "ida64",
    "idaq64",
    "ghidra",
    "ghidrarun",
    "radare2",
    "r2",

    // Debuggers / runtime inspectors
    "x64dbg",
    "x32dbg",
    "ollydbg",
    "ollydbg.exe",
    "ImmunityDebugger",
    "scylla",
    "procmon",            // Sysinternals Process Monitor
    "procmon.exe",
    "processhacker",
    "processhacker.exe",

    // Dynamic instrumentation / mobile tooling
    "frida",
    "frida-server",
    "frida-server.exe",
    "adb",
    "adb.exe",
    "jdb",
    "jdb.exe",

    // Android / APK tools (GUI/launcher names)
    "jadx",
    "jadx-gui",
    "apktool",

    // Misc / network scanners & analysis
    "nmap",
    "ncat",
    "netcat",
    "scapy"
};


            // Process name patterns (case insensitive)
            _processPatterns = new List<string>
            {
                "*debug*",
                "*proxy*",
                "*sniff*",
                "*analyzer*",
                "*monitor*",
                "*inspector*"
            };

            _cancellationTokenSource = new CancellationTokenSource();
        }

        public void StartMonitoring()
        {
            if (_isMonitoring) return;

            _isMonitoring = true;
            _cancellationTokenSource = new CancellationTokenSource();
            _monitoringTask = Task.Run(async () => await MonitorProcessesAsync(_cancellationTokenSource.Token));

            OnLogMessageReceived("🔒 Bypass Process is Ready");
        }

        public void StopMonitoring()
        {
            if (!_isMonitoring) return;

            _isMonitoring = false;
            _cancellationTokenSource?.Cancel();

            try
            {
                _monitoringTask?.Wait(3000);
            }
            catch (AggregateException)
            {
                // Task cancellation expected
            }

            OnLogMessageReceived("🔓 .. 🔓");
        }

        private async Task MonitorProcessesAsync(CancellationToken cancellationToken)
        {
            OnLogMessageReceived("🛡️ Background process protection activated");

            while (!cancellationToken.IsCancellationRequested)
            {
                try
                {
                    CheckAndKillProcesses();
                    await Task.Delay(2000, cancellationToken); // Check every 2 seconds
                }
                catch (TaskCanceledException)
                {
                    // Expected when stopping
                    break;
                }
                catch (Exception ex)
                {
                    OnLogMessageReceived($"⚠️ Process monitor error: {ex.Message}");
                    await Task.Delay(5000, cancellationToken);
                }
            }
        }

        private void CheckAndKillProcesses()
        {
            var allProcesses = Process.GetProcesses();

            foreach (var process in allProcesses)
            {
                try
                {
                    if (string.IsNullOrEmpty(process.ProcessName))
                        continue;

                    var processName = process.ProcessName.ToLower();

                    // Check exact matches
                    bool shouldKill = _processesToKill.Any(p =>
                        processName.Equals(p, StringComparison.OrdinalIgnoreCase));

                    // Check pattern matches
                    if (!shouldKill)
                    {
                        shouldKill = _processPatterns.Any(pattern =>
                        {
                            var cleanPattern = pattern.Replace("*", "").ToLower();
                            return processName.Contains(cleanPattern);
                        });
                    }

                    // Additional checks for debuggers and proxies
                    if (!shouldKill)
                    {
                        shouldKill = IsSuspiciousProcess(process);
                    }

                    if (shouldKill)
                    {
                        KillProcess(process);
                    }
                }
                catch (Exception ex)
                {
                    // Process might have exited already, or we don't have permissions
                    Debug.WriteLine($"Could not check process {process.ProcessName}: {ex.Message}");
                }
            }
        }

        private bool IsSuspiciousProcess(Process process)
        {
            try
            {
                var processName = process.ProcessName.ToLower();

                // Common debuggers
                var debuggers = new[] { "ollydbg", "x64dbg", "x32dbg", "windbg", "ida", "immunity" };
                if (debuggers.Any(d => processName.Contains(d)))
                    return true;

                // Common proxy tools
                var proxies = new[] { "proxyman", "zap", "mitm", "packet", "traffic" };
                if (proxies.Any(p => processName.Contains(p)))
                    return true;

                // Check if process has network monitoring capabilities
                if (processName.Contains("tcp") && processName.Contains("view"))
                    return true;

                // Check for any process with "hook" in name
                if (processName.Contains("hook"))
                    return true;

                return false;
            }
            catch
            {
                return false;
            }
        }

        private void KillProcess(Process process)
        {
            try
            {
                var processName = process.ProcessName;
                var processId = process.Id;

                // Try graceful shutdown first
                if (!process.HasExited)
                {
                    process.Kill();

                    // Wait for process to exit
                    if (process.WaitForExit(3000))
                    {
                        OnProcessKilled(processName, processId, "Terminated");
                        OnLogMessageReceived($"❌ Blocked: {processName} (PID: {processId})");
                    }
                    else
                    {
                        OnLogMessageReceived($"⚠️ Could not terminate: {processName} (PID: {processId})");
                    }
                }
            }
            catch (Exception ex)
            {
                OnLogMessageReceived($"⚠️ Failed to kill process {process.ProcessName}: {ex.Message}");
            }
        }

        // Method to manually kill a specific process by name
        public bool KillProcessByName(string processName)
        {
            try
            {
                var processes = Process.GetProcessesByName(processName);
                if (processes.Length == 0)
                    return false;

                bool killedAny = false;
                foreach (var process in processes)
                {
                    try
                    {
                        if (!process.HasExited)
                        {
                            process.Kill();
                            process.WaitForExit(2000);
                            killedAny = true;
                            OnProcessKilled(process.ProcessName, process.Id, "Manually terminated");
                        }
                    }
                    catch
                    {
                        // Continue with other processes
                    }
                }

                return killedAny;
            }
            catch (Exception ex)
            {
                OnLogMessageReceived($"⚠️ Manual kill failed for {processName}: {ex.Message}");
                return false;
            }
        }

        // Method to add temporary processes to kill list
        public void AddProcessToKillList(string processName)
        {
            if (!_processesToKill.Contains(processName, StringComparer.OrdinalIgnoreCase))
            {
                _processesToKill.Add(processName);
                OnLogMessageReceived($"📋 Added to kill list: {processName}");
            }
        }

        // Method to check if a specific process is running
        public bool IsProcessRunning(string processName)
        {
            return Process.GetProcessesByName(processName).Length > 0;
        }

        // Get list of currently running suspicious processes
        public List<string> GetRunningSuspiciousProcesses()
        {
            var suspicious = new List<string>();
            var allProcesses = Process.GetProcesses();

            foreach (var process in allProcesses)
            {
                try
                {
                    var processName = process.ProcessName.ToLower();

                    if (_processesToKill.Any(p => processName.Equals(p, StringComparison.OrdinalIgnoreCase)) ||
                        _processPatterns.Any(pattern => processName.Contains(pattern.Replace("*", "").ToLower())) ||
                        IsSuspiciousProcess(process))
                    {
                        suspicious.Add($"{process.ProcessName} (PID: {process.Id})");
                    }
                }
                catch
                {
                    // Ignore processes we can't access
                }
            }

            return suspicious;
        }

        #region Event Invokers
        private void OnProcessKilled(string processName, int processId, string reason)
        {
            ProcessKilled?.Invoke(this, new ProcessKilledEventArgs(processName, processId, reason));
        }

        private void OnLogMessageReceived(string message)
        {
            //LogMessageReceived?.Invoke(this, new LogMessageEventArgs(message));
        }
        #endregion

        public void Dispose()
        {
            StopMonitoring();
            _cancellationTokenSource?.Dispose();
        }
    }

    public class ProcessKilledEventArgs : EventArgs
    {
        public string ProcessName { get; }
        public int ProcessId { get; }
        public string Reason { get; }

        public ProcessKilledEventArgs(string processName, int processId, string reason)
        {
            ProcessName = processName;
            ProcessId = processId;
            Reason = reason;
        }
    }
}