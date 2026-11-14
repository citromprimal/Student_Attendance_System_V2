package com.primal.studentattendancesystemv2

import android.media.AudioManager
import android.media.ToneGenerator
import android.nfc.NfcAdapter
import android.nfc.NfcManager
import android.nfc.Tag
import android.os.Build
import android.os.Bundle
import android.os.VibrationEffect
import android.os.Vibrator
import android.os.VibratorManager
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.lifecycleScope
import com.primal.studentattendancesystemv2.model.ScanState
import com.primal.studentattendancesystemv2.ui.AttendanceScreen
import com.primal.studentattendancesystemv2.ui.theme.StudentAttendanceSystemV2Theme
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

class MainActivity : ComponentActivity(), NfcAdapter.ReaderCallback {

    private var nfcAdapter: NfcAdapter? = null

    private var scanState by mutableStateOf<ScanState>(
        ScanState.Waiting
    )

    private var lastCardId: String? = null
    private var lastScanTime: Long = 0L

    private val scanCooldown = 4000L

    private val toneGenerator =
        ToneGenerator(
            AudioManager.STREAM_NOTIFICATION,
            100
        )

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val nfcManager =
            getSystemService(NfcManager::class.java)

        nfcAdapter = nfcManager.defaultAdapter

        updateNfcStatus()

        enableEdgeToEdge()

        setContent {
            StudentAttendanceSystemV2Theme {

                AttendanceScreen(
                    scanState = scanState
                )
            }
        }
    }

    override fun onResume() {
        super.onResume()

        updateNfcStatus()

        if (nfcAdapter?.isEnabled == true) {

            nfcAdapter?.enableReaderMode(
                this,
                this,
                NfcAdapter.FLAG_READER_NFC_A or
                        NfcAdapter.FLAG_READER_NFC_B or
                        NfcAdapter.FLAG_READER_NFC_F or
                        NfcAdapter.FLAG_READER_NFC_V,
                null
            )
        }
    }

    override fun onPause() {
        super.onPause()

        nfcAdapter?.disableReaderMode(this)
    }

    override fun onTagDiscovered(tag: Tag) {

        val cardId = tag.id.toHexString()

        val currentTime =
            System.currentTimeMillis()

        // Ignorovanie opakovaného načítania rovnakej karty
        if (
            cardId == lastCardId &&
            currentTime - lastScanTime < scanCooldown
        ) {
            return
        }

        lastCardId = cardId
        lastScanTime = currentTime

        runOnUiThread {

            scanState =
                ScanState.Success(cardId)

            playSuccessSound()
            vibrateSuccess()

            lifecycleScope.launch {

                delay(1500)

                scanState =
                    ScanState.Waiting
            }
        }
    }

    private fun updateNfcStatus() {

        scanState = when {

            nfcAdapter == null ->
                ScanState.NfcUnsupported

            nfcAdapter?.isEnabled == false ->
                ScanState.NfcDisabled

            else ->
                ScanState.Waiting
        }
    }

    private fun playSuccessSound() {

        toneGenerator.startTone(
            ToneGenerator.TONE_PROP_ACK,
            200
        )
    }

    private fun playErrorSound() {

        toneGenerator.startTone(
            ToneGenerator.TONE_PROP_NACK,
            400
        )
    }

    private fun vibrateSuccess() {

        val vibrator = getVibrator()

        vibrator.vibrate(
            VibrationEffect.createOneShot(
                150,
                VibrationEffect.DEFAULT_AMPLITUDE
            )
        )
    }

    private fun vibrateError() {

        val vibrator = getVibrator()

        vibrator.vibrate(
            VibrationEffect.createWaveform(
                longArrayOf(
                    0,
                    200,
                    100,
                    200
                ),
                -1
            )
        )
    }

    private fun getVibrator(): Vibrator {

        return if (
            Build.VERSION.SDK_INT >=
            Build.VERSION_CODES.S
        ) {

            val vibratorManager =
                getSystemService(
                    VibratorManager::class.java
                )

            vibratorManager.defaultVibrator

        } else {

            @Suppress("DEPRECATION")
            getSystemService(
                VIBRATOR_SERVICE
            ) as Vibrator
        }
    }

    private fun ByteArray.toHexString(): String {

        return joinToString("") {
            "%02X".format(
                it.toInt() and 0xFF
            )
        }
    }

    override fun onDestroy() {

        toneGenerator.release()

        super.onDestroy()
    }
}