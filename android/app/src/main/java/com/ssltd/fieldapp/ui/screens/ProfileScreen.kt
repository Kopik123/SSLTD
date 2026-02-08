package com.ssltd.fieldapp.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.ssltd.fieldapp.BuildConfig
import com.ssltd.fieldapp.data.AuthStore

@Composable
fun ProfileScreen(authStore: AuthStore, onLogout: () -> Unit) {
  val user = authStore.getUser()
  Surface(modifier = Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) {
    Column(
      modifier = Modifier.padding(PaddingValues(16.dp)),
      verticalArrangement = Arrangement.spacedBy(10.dp),
    ) {
      Text(
        text = "Profile",
        style = MaterialTheme.typography.headlineSmall,
        color = MaterialTheme.colorScheme.onBackground,
      )

      if (user != null) {
        Text(text = user.name, color = MaterialTheme.colorScheme.onBackground)
        Text(text = user.email, color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f))
        Text(text = "Role: ${user.role}", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f))
      } else {
        Text(text = "Not signed in.", color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.75f))
      }

      Text(
        text = "API: ${BuildConfig.API_BASE_URL}",
        color = MaterialTheme.colorScheme.onBackground.copy(alpha = 0.65f),
        style = MaterialTheme.typography.bodySmall,
      )

      Button(onClick = onLogout) {
        Text("Sign out")
      }
    }
  }
}
