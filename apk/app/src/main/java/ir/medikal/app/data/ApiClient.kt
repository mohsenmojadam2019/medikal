package ir.medikal.app.data

import ir.medikal.app.BuildConfig
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject
import java.io.BufferedReader
import java.net.HttpURLConnection
import java.net.URL
import java.net.URLEncoder
import java.util.concurrent.ConcurrentHashMap

data class ApiResult(val ok: Boolean, val data: Any? = null, val message: String = "", val status: Int = 0)

class ApiClient(private val session: SessionStore) {
    suspend fun get(path: String, query: Map<String, String> = emptyMap()): ApiResult {
        val key = path + query.toString() + session.token.take(8)
        cache[key]?.takeIf { System.currentTimeMillis() - it.first < 30_000 }?.let { return it.second }
        return request("GET", path, query = query).also { if (it.ok) cache[key] = System.currentTimeMillis() to it }
    }
    suspend fun post(path: String, body: JSONObject = JSONObject()) = request("POST", path, body)
    suspend fun put(path: String, body: JSONObject = JSONObject()) = request("PUT", path, body)
    suspend fun delete(path: String) = request("DELETE", path)

    private suspend fun request(
        method: String,
        path: String,
        body: JSONObject? = null,
        query: Map<String, String> = emptyMap()
    ): ApiResult = withContext(Dispatchers.IO) {
        try {
            val base = session.apiUrl.trim().let { if (it.endsWith('/')) it else "$it/" }
            val qs = query.filterValues { it.isNotBlank() }.entries.joinToString("&") {
                "${URLEncoder.encode(it.key, "UTF-8") }=${URLEncoder.encode(it.value, "UTF-8") }"
            }
            val target = base + path.trimStart('/') + if (qs.isEmpty()) "" else "?$qs"
            val connection = URL(target).openConnection() as HttpURLConnection
            connection.requestMethod = method
            connection.connectTimeout = 12_000
            connection.readTimeout = 25_000
            connection.setRequestProperty("Accept", "application/json")
            connection.setRequestProperty("Content-Type", "application/json; charset=utf-8")
            connection.setRequestProperty("Accept-Language", "fa")
            session.token.takeIf { it.isNotBlank() }?.let {
                connection.setRequestProperty("Authorization", "Bearer $it")
            }
            if (body != null && method != "GET") {
                connection.doOutput = true
                connection.outputStream.use { it.write(body.toString().toByteArray(Charsets.UTF_8)) }
            }
            val code = connection.responseCode
            val stream = if (code in 200..299) connection.inputStream else connection.errorStream
            val text = stream?.bufferedReader()?.use(BufferedReader::readText).orEmpty()
            val json = if (text.startsWith("{")) JSONObject(text) else null
            val ok = code in 200..299 && (json?.optBoolean("success", true) != false)
            val payload = when {
                json == null -> text
                json.has("data") -> json.opt("data")
                else -> json
            }
            ApiResult(ok, payload, json?.optString("message").orEmpty(), code)
        } catch (e: Exception) {
            ApiResult(false, message = e.localizedMessage ?: "خطا در اتصال به سرور")
        }
    }

    companion object {
        private val cache = ConcurrentHashMap<String, Pair<Long, ApiResult>>()
        fun arrayFrom(value: Any?): JSONArray = when (value) {
            is JSONArray -> value
            is JSONObject -> when {
                value.opt("data") is JSONArray -> value.optJSONArray("data") ?: JSONArray()
                value.opt("items") is JSONArray -> value.optJSONArray("items") ?: JSONArray()
                value.opt("doctors") is JSONArray -> value.optJSONArray("doctors") ?: JSONArray()
                value.opt("products") is JSONArray -> value.optJSONArray("products") ?: JSONArray()
                else -> JSONArray()
            }
            else -> JSONArray()
        }

        fun objects(value: Any?): List<JSONObject> {
            val array = arrayFrom(value)
            return (0 until array.length()).mapNotNull { array.optJSONObject(it) }
        }
    }
}
