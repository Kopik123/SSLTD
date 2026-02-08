package com.ssltd.fieldapp.data

import android.content.Context
import android.content.SharedPreferences
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow

data class StoredUser(
  val id: Int,
  val role: String,
  val name: String,
  val email: String,
)

class AuthStore(context: Context) {
  private val prefs: SharedPreferences = createPrefs(context.applicationContext)
  private val tokenState = MutableStateFlow<String?>(prefs.getString(KEY_TOKEN, null))

  val tokenFlow: StateFlow<String?> = tokenState.asStateFlow()

  fun getToken(): String? = tokenState.value
  fun token(): String? = tokenState.value

  fun getUser(): StoredUser? {
    val id = prefs.getInt(KEY_USER_ID, 0)
    val role = prefs.getString(KEY_USER_ROLE, null)
    val name = prefs.getString(KEY_USER_NAME, null)
    val email = prefs.getString(KEY_USER_EMAIL, null)
    if (id <= 0 || role.isNullOrBlank() || name.isNullOrBlank() || email.isNullOrBlank()) {
      return null
    }
    return StoredUser(id = id, role = role, name = name, email = email)
  }

  fun saveSession(token: String, user: StoredUser) {
    prefs.edit()
      .putString(KEY_TOKEN, token)
      .putInt(KEY_USER_ID, user.id)
      .putString(KEY_USER_ROLE, user.role)
      .putString(KEY_USER_NAME, user.name)
      .putString(KEY_USER_EMAIL, user.email)
      .apply()
    tokenState.value = token
  }

  fun clear() {
    prefs.edit().clear().apply()
    tokenState.value = null
  }

  private fun createPrefs(context: Context): SharedPreferences {
    return try {
      val masterKey = MasterKey.Builder(context)
        .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
        .build()

      EncryptedSharedPreferences.create(
        context,
        PREFS_NAME,
        masterKey,
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM,
      )
    } catch (_: Throwable) {
      // Best-effort fallback: avoid app crash on devices without proper keystore support.
      context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
    }
  }

  private companion object {
    private const val PREFS_NAME = "ss_auth"
    private const val KEY_TOKEN = "token"
    private const val KEY_USER_ID = "user_id"
    private const val KEY_USER_ROLE = "user_role"
    private const val KEY_USER_NAME = "user_name"
    private const val KEY_USER_EMAIL = "user_email"
  }
}
