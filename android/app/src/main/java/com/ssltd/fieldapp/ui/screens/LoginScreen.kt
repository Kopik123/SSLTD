package com.ssltd.fieldapp.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import com.ssltd.fieldapp.data.AuthStore
import com.ssltd.fieldapp.data.StoredUser
import com.ssltd.fieldapp.data.api.LoginRequest
import com.ssltd.fieldapp.data.api.SsApi
import kotlinx.coroutines.launch

@Composable
fun LoginScreen(
  api: SsApi,
  authStore: AuthStore,
  onLoggedIn: () -> Unit,
) {
  val scope = rememberCoroutineScope()
  var email by remember { mutableStateOf("") }
  var password by remember { mutableStateOf("") }
  var loading by remember { mutableStateOf(false) }
  var error by remember { mutableStateOf<String?>(null) }

  Surface(modifier = Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) {
    Column(
      modifier = Modifier.padding(PaddingValues(20.dp)),
      verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
      Text(
        text = "S&S LTD Field App",
        style = MaterialTheme.typography.headlineSmall,
        color = MaterialTheme.colorScheme.onBackground,
      )
      Text(
        text = "Sign in to sync projects, messages, uploads, and timesheets.",
        style = MaterialTheme.typography.bodyMedium,
        color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f),
      )

      if (!error.isNullOrBlank()) {
        Text(
          text = error ?: "",
          color = MaterialTheme.colorScheme.error,
          style = MaterialTheme.typography.bodyMedium,
          modifier = Modifier.padding(top = 4.dp),
        )
      }

      OutlinedTextField(
        value = email,
        onValueChange = { email = it },
        label = { Text("Email") },
        enabled = !loading,
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
        modifier = Modifier.fillMaxWidth(),
        singleLine = true,
      )

      OutlinedTextField(
        value = password,
        onValueChange = { password = it },
        label = { Text("Password") },
        enabled = !loading,
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
        visualTransformation = PasswordVisualTransformation(),
        modifier = Modifier.fillMaxWidth(),
        singleLine = true,
      )

      Button(
        onClick = {
          if (loading) return@Button
          error = null
          loading = true
          scope.launch {
            try {
              val res = api.login(LoginRequest(email = email.trim(), password = password))
              authStore.saveSession(
                token = res.token,
                user = StoredUser(
                  id = res.user.id,
                  role = res.user.role,
                  name = res.user.name,
                  email = res.user.email,
                ),
              )
              onLoggedIn()
            } catch (t: Throwable) {
              error = t.message ?: "Login failed."
            } finally {
              loading = false
            }
          }
        },
        enabled = !loading,
      ) {
        Text(if (loading) "Signing in..." else "Sign in")
      }

      Text(
        text = "Dev base URL: ${com.ssltd.fieldapp.BuildConfig.API_BASE_URL}",
        style = MaterialTheme.typography.bodySmall,
        color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.65f),
        modifier = Modifier.padding(top = 10.dp),
      )
    }
  }
}
