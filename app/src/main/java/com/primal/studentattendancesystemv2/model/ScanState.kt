package com.primal.studentattendancesystemv2.model

sealed class ScanState {

    data object Waiting : ScanState()

    data class Success(
        val cardId: String
    ) : ScanState()

    data class Error(
        val message: String
    ) : ScanState()

    data object NfcDisabled : ScanState()

    data object NfcUnsupported : ScanState()
}