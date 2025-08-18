package com.mozoqr.app;

import android.annotation.SuppressLint;
import android.content.Intent;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.PowerManager;
import android.provider.Settings;
import android.util.Log;

import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    private static final String TAG = "MainActivity";

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
    // (Opcional) Solicitar ignorar optimizaciones de batería para mejorar recepción en background
    requestBatteryOptimizationDisable();
    // Nota: Ya NO se inicia manualmente MyFirebaseMessagingService. Firebase se encarga.
    }

    @SuppressLint("BatteryLife")
    private void requestBatteryOptimizationDisable() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
            PowerManager powerManager = (PowerManager) getSystemService(POWER_SERVICE);
            String packageName = getPackageName();
            
            if (!powerManager.isIgnoringBatteryOptimizations(packageName)) {
                Log.d(TAG, "Solicitando ignorar optimizaciones de batería");
                try {
                    Intent intent = new Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS);
                    intent.setData(Uri.parse("package:" + packageName));
                    startActivity(intent);
                } catch (Exception e) {
                    Log.w(TAG, "No se pudo abrir configuración de batería", e);
                }
            } else {
                Log.d(TAG, "Optimizaciones de batería ya deshabilitadas");
            }
        }
    }
    
    // Eliminado método startFirebaseForegroundService (no necesario y podía causar fallo si no se llamaba a startForeground())
}
