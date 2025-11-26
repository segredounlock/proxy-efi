using System;
using System.IO;
using Microsoft.Win32;

namespace iSkorpionA12.Helper
{
	// Token: 0x0200001B RID: 27
	public class DLLHelper
	{
		// Token: 0x06000182 RID: 386 RVA: 0x000080F8 File Offset: 0x000062F8
		public static string GetiTunesMobileDeviceDllPath()
		{
			RegistryKey registryKey = Registry.LocalMachine.OpenSubKey("SOFTWARE\\Apple Inc.\\Apple Mobile Device Support\\Shared");
			bool flag = registryKey != null;
			if (flag)
			{
				string text = registryKey.GetValue("MobileDeviceDLL") as string;
				bool flag2 = !string.IsNullOrWhiteSpace(text);
				if (flag2)
				{
					FileInfo fileInfo = new FileInfo(text);
					bool exists = fileInfo.Exists;
					if (exists)
					{
						return fileInfo.DirectoryName;
					}
				}
			}
			string text2 = Environment.GetFolderPath(Environment.SpecialFolder.CommonProgramFiles) + "\\Apple\\Mobile Device Support";
			bool flag3 = File.Exists(text2 + "\\MobileDevice.dll");
			string result;
			if (flag3)
			{
				result = text2;
			}
			else
			{
				text2 = Environment.GetFolderPath(Environment.SpecialFolder.CommonProgramFilesX86) + "\\Apple\\Mobile Device Support";
				bool flag4 = File.Exists(text2 + "\\MobileDevice.dll");
				if (flag4)
				{
					result = text2;
				}
				else
				{
					result = string.Empty;
				}
			}
			return result;
		}

		// Token: 0x06000183 RID: 387 RVA: 0x000081CC File Offset: 0x000063CC
		public static string GetAppleApplicationSupportFolder()
		{
			RegistryKey registryKey = Registry.LocalMachine.OpenSubKey("SOFTWARE\\Apple Inc.\\Apple Mobile Device Support");
			bool flag = registryKey != null;
			if (flag)
			{
				string text = registryKey.GetValue("InstallDir") as string;
				bool flag2 = !string.IsNullOrWhiteSpace(text);
				if (flag2)
				{
					return text;
				}
			}
			string text2 = Environment.GetFolderPath(Environment.SpecialFolder.CommonProgramFiles) + "\\Apple\\Mobile Device Support";
			bool flag3 = File.Exists(text2 + "\\CoreFoundation.dll");
			string result;
			if (flag3)
			{
				result = text2;
			}
			else
			{
				text2 = Environment.GetFolderPath(Environment.SpecialFolder.CommonProgramFilesX86) + "\\Apple\\Mobile Device Support";
				bool flag4 = File.Exists(text2 + "\\CoreFoundation.dll");
				if (flag4)
				{
					result = text2;
				}
				else
				{
					result = string.Empty;
				}
			}
			return result;
		}
	}
}
