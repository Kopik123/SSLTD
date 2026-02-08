package com.ssltd.fieldapp.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.runtime.Composable

private val DarkScheme = darkColorScheme(
  primary = ImperialGold,
  secondary = ImperialGoldDark,
  background = RomanBlack,
  surface = Stone,
  onPrimary = RomanBlack,
  onSecondary = RomanBlack,
  onBackground = MarbleWhite,
  onSurface = MarbleWhite,
)

@Composable
fun SSTheme(content: @Composable () -> Unit) {
  MaterialTheme(
    colorScheme = DarkScheme,
    content = content
  )
}

