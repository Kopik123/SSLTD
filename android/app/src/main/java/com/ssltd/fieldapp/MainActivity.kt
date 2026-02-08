package com.ssltd.fieldapp

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.ChatBubbleOutline
import androidx.compose.material.icons.outlined.FolderOpen
import androidx.compose.material.icons.outlined.PersonOutline
import androidx.compose.material.icons.outlined.PhotoCamera
import androidx.compose.material.icons.outlined.Today
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.runtime.collectAsState
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.navigation.NavType
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import com.ssltd.fieldapp.data.AuthStore
import com.ssltd.fieldapp.data.NetworkMonitor
import com.ssltd.fieldapp.data.api.ApiClient
import com.ssltd.fieldapp.data.api.SsApi
import com.ssltd.fieldapp.ui.screens.CaptureScreen
import com.ssltd.fieldapp.ui.screens.AddReportScreen
import com.ssltd.fieldapp.ui.screens.IssuesScreen
import com.ssltd.fieldapp.ui.screens.LoginScreen
import com.ssltd.fieldapp.ui.screens.MessagesScreen
import com.ssltd.fieldapp.ui.screens.ProjectDetailScreen
import com.ssltd.fieldapp.ui.screens.ProjectChecklistScreen
import com.ssltd.fieldapp.ui.screens.ProfileScreen
import com.ssltd.fieldapp.ui.screens.ProjectsScreen
import com.ssltd.fieldapp.ui.screens.TodayScreen
import com.ssltd.fieldapp.ui.theme.SSTheme

class MainActivity : ComponentActivity() {
  override fun onCreate(savedInstanceState: Bundle?) {
    super.onCreate(savedInstanceState)
    setContent {
      SSTheme {
        Root()
      }
    }
  }
}

private data class NavItem(
  val route: String,
  val label: String,
  val icon: @Composable () -> Unit,
)

@Composable
private fun Root() {
  val context = LocalContext.current
  val authStore = remember { AuthStore(context) }
  val api = remember { ApiClient.create(authStore) }
  val networkMonitor = remember { NetworkMonitor(context) }
  DisposableEffect(Unit) {
    networkMonitor.start()
    onDispose { networkMonitor.stop() }
  }

  val isOnline by networkMonitor.online.collectAsState()
  val token by authStore.tokenFlow.collectAsState(initial = authStore.getToken())

  if (token.isNullOrBlank()) {
    LoginScreen(
      api = api,
      authStore = authStore,
      onLoggedIn = { /* Root reacts to tokenFlow */ },
    )
  } else {
    AppScaffold(
      api = api,
      authStore = authStore,
      isOnline = isOnline,
      onLogout = {
        authStore.clear()
      },
    )
  }
}

@Composable
private fun AppScaffold(
  api: SsApi,
  authStore: AuthStore,
  isOnline: Boolean,
  onLogout: () -> Unit,
) {
  val navController = rememberNavController()
  val items = listOf(
    NavItem("today", "Today") { Icon(Icons.Outlined.Today, contentDescription = null) },
    NavItem("projects", "Projects") { Icon(Icons.Outlined.FolderOpen, contentDescription = null) },
    NavItem("capture", "Capture") { Icon(Icons.Outlined.PhotoCamera, contentDescription = null) },
    NavItem("messages", "Messages") { Icon(Icons.Outlined.ChatBubbleOutline, contentDescription = null) },
    NavItem("profile", "Profile") { Icon(Icons.Outlined.PersonOutline, contentDescription = null) },
  )

  val navBackStackEntry by navController.currentBackStackEntryAsState()
  val currentRoute = navBackStackEntry?.destination?.route
  val navGroup = when {
    currentRoute == null -> null
    currentRoute.startsWith("project/") -> "projects"
    else -> currentRoute
  }

  Scaffold(
    topBar = {
      if (!isOnline) {
        Text(
          text = "Offline (uploads will queue).",
          modifier = Modifier.padding(horizontal = 16.dp, vertical = 10.dp),
        )
      }
    },
    bottomBar = {
      NavigationBar {
        items.forEach { item ->
          val selected = navGroup == item.route
          NavigationBarItem(
            selected = selected,
            onClick = {
              navController.navigate(item.route) {
                popUpTo(navController.graph.findStartDestination().id) { saveState = true }
                launchSingleTop = true
                restoreState = true
              }
            },
            icon = item.icon,
            label = { Text(item.label) },
          )
        }
      }
    }
  ) { padding ->
    Box(modifier = Modifier.padding(padding)) {
      NavHost(navController = navController, startDestination = "today") {
        composable("today") { TodayScreen(api) }
        composable("projects") {
          ProjectsScreen(
            api = api,
            isOnline = isOnline,
            onOpenProject = { id -> navController.navigate("project/$id") },
          )
        }
        composable(
          route = "project/{id}",
          arguments = listOf(navArgument("id") { type = NavType.IntType }),
        ) { entry ->
          val id = entry.arguments?.getInt("id") ?: 0
          ProjectDetailScreen(
            api = api,
            isOnline = isOnline,
            projectId = id,
            onBack = { navController.popBackStack() },
            onOpenChecklist = { navController.navigate("project/$id/checklist") },
            onAddReport = { navController.navigate("project/$id/report") },
            onOpenIssues = { navController.navigate("project/$id/issues") },
          )
        }
        composable(
          route = "project/{id}/report",
          arguments = listOf(navArgument("id") { type = NavType.IntType }),
        ) { entry ->
          val id = entry.arguments?.getInt("id") ?: 0
          AddReportScreen(
            api = api,
            projectId = id,
            isOnline = isOnline,
            onBack = { navController.popBackStack() },
          )
        }
        composable(
          route = "project/{id}/issues",
          arguments = listOf(navArgument("id") { type = NavType.IntType }),
        ) { entry ->
          val id = entry.arguments?.getInt("id") ?: 0
          IssuesScreen(
            api = api,
            projectId = id,
            isOnline = isOnline,
            onBack = { navController.popBackStack() },
          )
        }
        composable(
          route = "project/{id}/checklist",
          arguments = listOf(navArgument("id") { type = NavType.IntType }),
        ) { entry ->
          val id = entry.arguments?.getInt("id") ?: 0
          ProjectChecklistScreen(
            api = api,
            projectId = id,
            isOnline = isOnline,
            onBack = { navController.popBackStack() },
          )
        }
        composable("capture") { CaptureScreen(api = api, authStore = authStore) }
        composable("messages") { MessagesScreen(api, isOnline = isOnline) }
        composable("profile") { ProfileScreen(authStore, onLogout) }
      }
    }
  }
}
