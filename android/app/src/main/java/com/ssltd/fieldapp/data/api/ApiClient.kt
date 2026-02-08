package com.ssltd.fieldapp.data.api

import com.google.gson.FieldNamingPolicy
import com.google.gson.GsonBuilder
import com.ssltd.fieldapp.BuildConfig
import com.ssltd.fieldapp.data.AuthStore
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiClient {
  fun create(authStore: AuthStore): SsApi {
    val gson = GsonBuilder()
      .setFieldNamingPolicy(FieldNamingPolicy.LOWER_CASE_WITH_UNDERSCORES)
      .create()

    val logging = HttpLoggingInterceptor().apply {
      level = if (BuildConfig.DEBUG) HttpLoggingInterceptor.Level.BODY else HttpLoggingInterceptor.Level.NONE
    }

    val client = OkHttpClient.Builder()
      .addInterceptor { chain ->
        val token = authStore.token()
        val req = if (!token.isNullOrBlank()) {
          chain.request().newBuilder()
            .header("Authorization", "Bearer $token")
            .build()
        } else {
          chain.request()
        }
        val resp = chain.proceed(req)
        if (resp.code == 401) {
          // Force re-auth; Root observes tokenFlow and will route to Login.
          authStore.clear()
        }
        resp
      }
      .addInterceptor(logging)
      .connectTimeout(20, TimeUnit.SECONDS)
      .readTimeout(30, TimeUnit.SECONDS)
      .writeTimeout(30, TimeUnit.SECONDS)
      .build()

    val retrofit = Retrofit.Builder()
      .baseUrl(BuildConfig.API_BASE_URL)
      .client(client)
      .addConverterFactory(GsonConverterFactory.create(gson))
      .build()

    return retrofit.create(SsApi::class.java)
  }
}
