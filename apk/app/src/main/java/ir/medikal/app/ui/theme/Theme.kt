package ir.medikal.app.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val Monochrome = darkColorScheme(
    primary = Color.White,
    onPrimary = Color.Black,
    secondary = Color(0xFFD8D8D8),
    onSecondary = Color.Black,
    background = Color.Black,
    onBackground = Color.White,
    surface = Color(0xFF111111),
    onSurface = Color.White,
    surfaceVariant = Color(0xFF202020),
    onSurfaceVariant = Color(0xFFD0D0D0),
    outline = Color(0xFF555555),
    error = Color(0xFFFF6B6B)
)

@Composable
fun MedikalTheme(content: @Composable () -> Unit) {
    MaterialTheme(colorScheme = Monochrome, typography = MaterialTheme.typography, content = content)
}
