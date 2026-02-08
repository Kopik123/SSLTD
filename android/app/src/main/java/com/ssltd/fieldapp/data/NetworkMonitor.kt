package com.ssltd.fieldapp.data

import android.content.Context
import android.net.ConnectivityManager
import android.net.Network
import android.net.NetworkCapabilities
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow

class NetworkMonitor(context: Context) {
  private val cm = context.applicationContext.getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager
  private val onlineState = MutableStateFlow(isOnlineNow())
  val online: StateFlow<Boolean> = onlineState

  private val callback = object : ConnectivityManager.NetworkCallback() {
    override fun onAvailable(network: Network) {
      onlineState.value = isOnlineNow()
    }

    override fun onLost(network: Network) {
      onlineState.value = isOnlineNow()
    }

    override fun onCapabilitiesChanged(network: Network, networkCapabilities: NetworkCapabilities) {
      onlineState.value = isOnlineNow()
    }
  }

  fun start() {
    try {
      cm.registerDefaultNetworkCallback(callback)
      onlineState.value = isOnlineNow()
    } catch (_: Throwable) {
      // Best-effort; if callbacks are not supported, keep initial state.
    }
  }

  fun stop() {
    try {
      cm.unregisterNetworkCallback(callback)
    } catch (_: Throwable) {
      // ignore
    }
  }

  private fun isOnlineNow(): Boolean {
    return try {
      val network = cm.activeNetwork ?: return false
      val caps = cm.getNetworkCapabilities(network) ?: return false
      caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_INTERNET) &&
        caps.hasCapability(NetworkCapabilities.NET_CAPABILITY_VALIDATED)
    } catch (_: Throwable) {
      false
    }
  }
}

