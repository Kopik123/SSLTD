package com.ssltd.fieldapp

import android.app.Application
import io.sentry.android.core.SentryAndroid

class SsApp : Application() {
  override fun onCreate() {
    super.onCreate()

    val dsn = BuildConfig.SENTRY_DSN
    if (dsn.isBlank()) {
      return
    }

    SentryAndroid.init(this) { options ->
      options.dsn = dsn
      options.environment = if (BuildConfig.DEBUG) "debug" else "release"
      // Keep telemetry minimal for MVP.
      options.tracesSampleRate = 0.0
    }
  }
}
