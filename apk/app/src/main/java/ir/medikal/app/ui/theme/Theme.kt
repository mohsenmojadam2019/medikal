package ir.medikal.app.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val Monochrome = lightColorScheme(
    primary = Color(0xFF161616),
    onPrimary = Color.White,
    secondary = Color(0xFF4B4B4B),
    onSecondary = Color.White,
    background = Color(0xFFF4F4F4),
    onBackground = Color(0xFF111111),
    surface = Color.White,
    onSurface = Color(0xFF111111),
    surfaceVariant = Color(0xFFE9E9E9),
    onSurfaceVariant = Color(0xFF3D3D3D),
    outline = Color(0xFFBDBDBD),
    error = Color(0xFFB3261E)
)

@Composable
fun MedikalTheme(content: @Composable () -> Unit) {
    MaterialTheme(colorScheme = Monochrome, typography = MaterialTheme.typography, content = content)
}
