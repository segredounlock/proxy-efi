using System;
using System.Diagnostics;
using System.Net.Http;
using System.Text;
using System.Threading.Tasks;
using Newtonsoft.Json;

namespace iSkorpionA12
{
    public class TelegramNotifier : IDisposable
    {
        private readonly HttpClient _httpClient;
        private readonly string _apiUrl;
        private readonly string _toolVersion;

        public TelegramNotifier(string apiUrl, string toolVersion)
        {
            _httpClient = new HttpClient { Timeout = TimeSpan.FromSeconds(30) };
            _apiUrl = apiUrl;
            _toolVersion = toolVersion;
        }

        public async Task<bool> SendActivationSuccessAsync(string deviceModel, string serialNumber, string iosVersion)
        {
            try
            {
                var activationTime = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss");

                var payload = new
                {
                    action = "activation_success",
                    device_model = deviceModel,
                    serial_number = serialNumber,
                    ios_version = iosVersion,
                    activation_time = activationTime,
                    tool_version = _toolVersion
                };

                return await SendToTelegramAsync(payload);
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"❌ Notification error: {ex.Message}");
                return false;
            }
        }

        public async Task<bool> SendActivationErrorAsync(string deviceModel, string serialNumber, string iosVersion, string errorMessage = null)
        {
            try
            {
                var activationTime = DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss");

                var payload = new
                {
                    action = "activation_error",
                    device_model = deviceModel,
                    serial_number = serialNumber,
                    ios_version = iosVersion,
                    activation_time = activationTime,
                    tool_version = _toolVersion,
                    error_message = errorMessage
                };

                return await SendToTelegramAsync(payload);
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"❌ Notification error: {ex.Message}");
                return false;
            }
        }

        private async Task<bool> SendToTelegramAsync(object payload)
        {
            try
            {
                var json = JsonConvert.SerializeObject(payload);
                var content = new StringContent(json, Encoding.UTF8, "application/json");

                var response = await _httpClient.PostAsync(_apiUrl, content);

                if (response.IsSuccessStatusCode)
                {
                    var responseContent = await response.Content.ReadAsStringAsync();
                    var result = JsonConvert.DeserializeObject<TelegramResponse>(responseContent);

                    Debug.WriteLine($"✅ Telegram notification sent successfully");
                    return result?.Success == true;
                }
                else
                {
                    Debug.WriteLine($"❌ Telegram API error: {response.StatusCode}");
                    return false;
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"❌ Telegram communication error: {ex.Message}");
                return false;
            }
        }

        public void Dispose()
        {
            _httpClient?.Dispose();
        }
    }

    public class TelegramResponse
    {
        public bool Success { get; set; }
        public string Message { get; set; }
    }
}