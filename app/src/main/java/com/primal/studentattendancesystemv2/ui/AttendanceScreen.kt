package com.primal.studentattendancesystemv2.ui

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.primal.studentattendancesystemv2.model.ScanState

@Composable
fun AttendanceScreen(
    scanState: ScanState
) {

    val backgroundColor = when (scanState) {
        is ScanState.Success -> Color(0xFF2E7D32)
        is ScanState.Error -> Color(0xFFC62828)
        else -> MaterialTheme.colorScheme.background
    }

    val textColor = when (scanState) {
        is ScanState.Success,
        is ScanState.Error -> Color.White

        else -> MaterialTheme.colorScheme.onBackground
    }

    Scaffold(
        modifier = Modifier.fillMaxSize()
    ) { innerPadding ->

        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(backgroundColor)
                .padding(innerPadding)
                .padding(24.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center
        ) {

            when (scanState) {

                is ScanState.Waiting -> {
                    WaitingScreen(
                        textColor = textColor
                    )
                }

                is ScanState.Success -> {
                    SuccessScreen(
                        cardId = scanState.cardId
                    )
                }

                is ScanState.Error -> {
                    ErrorScreen(
                        message = scanState.message
                    )
                }

                is ScanState.NfcDisabled -> {
                    NfcDisabledScreen(
                        textColor = textColor
                    )
                }

                is ScanState.NfcUnsupported -> {
                    NfcUnsupportedScreen(
                        textColor = textColor
                    )
                }
            }
        }
    }
}

@Composable
private fun WaitingScreen(
    textColor: Color
) {

    Text(
        text = "Student Attendance System",
        fontSize = 30.sp,
        fontWeight = FontWeight.Bold,
        color = textColor
    )

    Text(
        text = "📱",
        fontSize = 60.sp,
        modifier = Modifier.padding(top = 40.dp)
    )

    Text(
        text = "Priložte ISIC kartu",
        fontSize = 22.sp,
        color = textColor,
        modifier = Modifier.padding(top = 24.dp)
    )

    Text(
        text = "NFC čítačka je aktívna",
        fontSize = 16.sp,
        color = textColor,
        modifier = Modifier.padding(top = 8.dp)
    )
}

@Composable
private fun SuccessScreen(
    cardId: String
) {

    Text(
        text = "✓",
        fontSize = 90.sp,
        fontWeight = FontWeight.Bold,
        color = Color.White
    )

    Text(
        text = "Karta načítaná",
        fontSize = 30.sp,
        fontWeight = FontWeight.Bold,
        color = Color.White,
        modifier = Modifier.padding(top = 16.dp)
    )

    Text(
        text = "Card ID",
        fontSize = 18.sp,
        color = Color.White,
        modifier = Modifier.padding(top = 32.dp)
    )

    Text(
        text = cardId,
        fontSize = 22.sp,
        fontWeight = FontWeight.Bold,
        color = Color.White,
        modifier = Modifier.padding(top = 8.dp)
    )
}

@Composable
private fun ErrorScreen(
    message: String
) {

    Text(
        text = "✕",
        fontSize = 90.sp,
        fontWeight = FontWeight.Bold,
        color = Color.White
    )

    Text(
        text = "Chyba pri načítaní",
        fontSize = 30.sp,
        fontWeight = FontWeight.Bold,
        color = Color.White,
        modifier = Modifier.padding(top = 16.dp)
    )

    Text(
        text = message,
        fontSize = 18.sp,
        color = Color.White,
        modifier = Modifier.padding(top = 20.dp)
    )
}

@Composable
private fun NfcDisabledScreen(
    textColor: Color
) {

    Text(
        text = "⚠️",
        fontSize = 70.sp
    )

    Text(
        text = "NFC je vypnuté",
        fontSize = 28.sp,
        fontWeight = FontWeight.Bold,
        color = textColor,
        modifier = Modifier.padding(top = 20.dp)
    )
}

@Composable
private fun NfcUnsupportedScreen(
    textColor: Color
) {

    Text(
        text = "❌",
        fontSize = 70.sp
    )

    Text(
        text = "Toto zariadenie nepodporuje NFC",
        fontSize = 24.sp,
        fontWeight = FontWeight.Bold,
        color = textColor,
        modifier = Modifier.padding(top = 20.dp)
    )
}