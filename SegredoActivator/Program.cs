using System;
using System.Windows.Forms;
using SegredoActivator.Forms;

namespace SegredoActivator
{
    internal static class Program
    {
        [STAThread]
        static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.SetHighDpiMode(HighDpiMode.SystemAware);
            
            // Iniciar com a tela principal moderna
            Application.Run(new MainForm());
        }
    }
}
