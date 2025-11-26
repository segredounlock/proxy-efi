using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Linq;
using System.Runtime.InteropServices;
using System.Threading;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace iSkorpionA12
{
    static class Program
    {
        /// <summary>
        /// The main entry point for the application.
        /// </summary>

        static System.Threading.Mutex singleton = new Mutex(true, "Azwyn");

        // Importar SetDllDirectory de Windows
        [DllImport("kernel32.dll", CharSet = CharSet.Unicode, SetLastError = true)]
        private static extern bool SetDllDirectory(string lpPathName);

        [STAThread]
        static void Main()
        {
            // ✅ PASO 1: CONFIGURAR DLLs NATIVAS ANTES QUE NADA (CRÍTICO)
            ConfigureNativeDllPaths();

            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);

            // ✅ PASO 2: VERIFICAR INSTANCIA ÚNICA
            if (!singleton.WaitOne(TimeSpan.Zero, true))
            {
                // Ya hay otra instancia corriendo
                MessageBox.Show("This Software is Already running", "[ERROR]", MessageBoxButtons.OK, MessageBoxIcon.Error);
                Process.GetCurrentProcess().Kill();
            }
            else
            {
                Application.Run(new Form1());
            }
        }

        private static void ConfigureNativeDllPaths()
        {
            try
            {
                // Obtener directorio del ejecutable
                string exeDir = AppDomain.CurrentDomain.BaseDirectory;

                // Determinar arquitectura
                bool is64Bit = Environment.Is64BitProcess;
                string nativeFolder = is64Bit ? "win-x64" : "win-x86";

                // Ruta completa a la carpeta nativa
                string nativePath = Path.Combine(exeDir, nativeFolder);

                Console.WriteLine($"[NATIVE DLL] ═══════════════════════════════════");
                Console.WriteLine($"[NATIVE DLL] Configurando ruta: {nativePath}");
                Console.WriteLine($"[NATIVE DLL] Arquitectura: {(is64Bit ? "64-bit" : "32-bit")}");

                // Verificar que existe
                if (!Directory.Exists(nativePath))
                {
                    Console.WriteLine($"[NATIVE DLL] ⚠️ Carpeta no encontrada: {nativePath}");
                    MessageBox.Show(
                        $"Native libraries folder not found:\n\n{nativePath}\n\n" +
                        "Please ensure the win-x86 or win-x64 folder exists with the required DLLs.",
                        "Missing Libraries",
                        MessageBoxButtons.OK,
                        MessageBoxIcon.Error
                    );
                    return;
                }

                // Método 1: SetDllDirectory (más efectivo)
                if (SetDllDirectory(nativePath))
                {
                    Console.WriteLine("[NATIVE DLL] ✅ SetDllDirectory exitoso");
                }
                else
                {
                    Console.WriteLine("[NATIVE DLL] ❌ SetDllDirectory falló");
                }

                // Método 2: Agregar al PATH (respaldo)
                string currentPath = Environment.GetEnvironmentVariable("PATH") ?? string.Empty;
                if (!currentPath.Contains(nativePath))
                {
                    Environment.SetEnvironmentVariable("PATH", nativePath + ";" + currentPath);
                    Console.WriteLine("[NATIVE DLL] ✅ Agregado al PATH");
                }

                Console.WriteLine($"[NATIVE DLL] ═══════════════════════════════════");
            }
            catch (Exception ex)
            {
                Console.WriteLine($"[NATIVE DLL] ❌ Error configurando rutas: {ex.Message}");
                MessageBox.Show(
                    $"Error loading native libraries:\n\n{ex.Message}\n\n" +
                    "The application may not work correctly.",
                    "Initialization Error",
                    MessageBoxButtons.OK,
                    MessageBoxIcon.Error
                );
            }
        }
    }
}