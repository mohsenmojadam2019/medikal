package ir.medikal.app.data

import android.content.Context
import ir.medikal.app.BuildConfig

class SessionStore(context: Context) {
    private val prefs = context.getSharedPreferences("medikal_session", Context.MODE_PRIVATE)
    var token: String
        get() = prefs.getString("token", "").orEmpty()
        set(value) = prefs.edit().putString("token", value).apply()
    var apiUrl: String
        get() = prefs.getString("api_url", BuildConfig.DEFAULT_API_URL).orEmpty()
        set(value) = prefs.edit().putString("api_url", value).apply()
    val isLoggedIn get() = token.isNotBlank()
    fun clear() = prefs.edit().remove("token").apply()
}
