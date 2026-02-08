plugins {
  id("com.android.application")
  id("org.jetbrains.kotlin.android")
  id("org.jetbrains.kotlin.kapt")
}

fun normalizeBaseUrl(raw: String): String {
  val t = raw.trim()
  if (t.isEmpty()) return t
  return if (t.endsWith("/")) t else "$t/"
}

android {
  namespace = "com.ssltd.fieldapp"
  compileSdk = 34

  signingConfigs {
    create("release") {
      // Optional: configure via env vars for CI/local signing.
      // If not provided, release builds fall back to debug signing (NOT for production).
      val storeFilePath = System.getenv("SS_RELEASE_STORE_FILE")
      if (!storeFilePath.isNullOrBlank()) {
        storeFile = file(storeFilePath)
        storePassword = System.getenv("SS_RELEASE_STORE_PASSWORD") ?: ""
        keyAlias = System.getenv("SS_RELEASE_KEY_ALIAS") ?: ""
        keyPassword = System.getenv("SS_RELEASE_KEY_PASSWORD") ?: ""
      }
    }
  }

  defaultConfig {
    applicationId = "com.ssltd.fieldapp"
    minSdk = 26
    targetSdk = 34
    versionCode = 1
    versionName = "0.1.0"

    // Debug uses XAMPP/PHP built-in server running on the host.
    // Emulator reaches host via 10.0.2.2.
    val debugBaseUrl = normalizeBaseUrl(System.getenv("SS_API_BASE_URL_DEBUG") ?: "http://10.0.2.2:8000/")
    buildConfigField("String", "API_BASE_URL", "\"${debugBaseUrl.replace("\"", "\\\"")}\"")
    val sentryDsn = System.getenv("SS_SENTRY_DSN") ?: ""
    buildConfigField("String", "SENTRY_DSN", "\"${sentryDsn.replace("\"", "\\\"")}\"")

    manifestPlaceholders["cleartextTrafficPermitted"] = "true"
  }

  buildTypes {
    debug {
      // allow http:// for local dev
      manifestPlaceholders["cleartextTrafficPermitted"] = "true"
    }
    release {
      isMinifyEnabled = true
      // release should use HTTPS; replace with real domain before shipping
      val releaseBaseUrl = normalizeBaseUrl(System.getenv("SS_API_BASE_URL") ?: "https://example.invalid/")
      val storeFilePath = System.getenv("SS_RELEASE_STORE_FILE")
      if (!storeFilePath.isNullOrBlank() && releaseBaseUrl.contains("example.invalid")) {
        throw RuntimeException("SS_API_BASE_URL must be set when building a signed release build.")
      }
      buildConfigField("String", "API_BASE_URL", "\"${releaseBaseUrl.replace("\"", "\\\"")}\"")
      val sentryDsn = System.getenv("SS_SENTRY_DSN") ?: ""
      buildConfigField("String", "SENTRY_DSN", "\"${sentryDsn.replace("\"", "\\\"")}\"")
      manifestPlaceholders["cleartextTrafficPermitted"] = "false"
      proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")

      signingConfig = if (!storeFilePath.isNullOrBlank()) {
        signingConfigs.getByName("release")
      } else {
        signingConfigs.getByName("debug")
      }
    }
  }

  compileOptions {
    sourceCompatibility = JavaVersion.VERSION_17
    targetCompatibility = JavaVersion.VERSION_17
  }
  kotlinOptions {
    jvmTarget = "17"
  }

  buildFeatures {
    compose = true
    buildConfig = true
  }
  composeOptions {
    kotlinCompilerExtensionVersion = "1.5.8"
  }

  packaging {
    resources {
      excludes += "/META-INF/{AL2.0,LGPL2.1}"
    }
  }
}

dependencies {
  implementation(platform("androidx.compose:compose-bom:2024.02.01"))
  implementation("androidx.core:core-ktx:1.12.0")
  implementation("androidx.activity:activity-compose:1.8.2")
  implementation("androidx.compose.ui:ui")
  implementation("androidx.compose.ui:ui-tooling-preview")
  implementation("androidx.compose.material3:material3")
  implementation("androidx.compose.material:material-icons-extended")
  implementation("androidx.navigation:navigation-compose:2.7.7")

  implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.8.1")
  implementation("com.squareup.okhttp3:okhttp:4.12.0")
  implementation("com.squareup.okhttp3:logging-interceptor:4.12.0")
  implementation("com.squareup.retrofit2:retrofit:2.11.0")
  implementation("com.squareup.retrofit2:converter-gson:2.11.0")
  implementation("androidx.security:security-crypto:1.1.0-alpha06")
  implementation("androidx.exifinterface:exifinterface:1.3.7")
  implementation("com.google.errorprone:error_prone_annotations:2.24.1")
  implementation("io.sentry:sentry-android:7.12.0")

  implementation("androidx.room:room-runtime:2.6.1")
  implementation("androidx.room:room-ktx:2.6.1")
  kapt("androidx.room:room-compiler:2.6.1")

  implementation("androidx.work:work-runtime-ktx:2.9.0")

  debugImplementation("androidx.compose.ui:ui-tooling")
  debugImplementation("androidx.compose.ui:ui-test-manifest")
}
