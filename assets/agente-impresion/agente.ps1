# Agente de impresión de RestoWeb (Windows PowerShell 5.1, sin dependencias).
# Pregunta al sistema cada 2 segundos si hay comandas para las impresoras de este equipo y las imprime en bruto
# (ESC/POS) en la impresora de Windows indicada en "Destino de impresión" o, si no está aquí, en IP y puerto.
# Configuración: config.json junto a este archivo ({"url": ".../index.php/AgenteImpresion/", "token": "..."}).
$ErrorActionPreference = 'Continue'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$dir = Split-Path -Parent $MyInvocation.MyCommand.Path
$logFile = Join-Path $dir 'agente.log'
function Log($m) {
    try {
        if ((Test-Path $logFile) -and (Get-Item $logFile).Length -gt 1MB) { Move-Item $logFile "$logFile.old" -Force }
        Add-Content -Path $logFile -Value ('{0} {1}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $m)
    } catch {}
}

Add-Type -TypeDefinition @"
using System;
using System.Runtime.InteropServices;
public class RawPrint {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    public class DOCINFO {
        [MarshalAs(UnmanagedType.LPWStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPWStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPWStr)] public string pDataType;
    }
    [DllImport("winspool.drv", EntryPoint = "OpenPrinterW", SetLastError = true, CharSet = CharSet.Unicode)] static extern bool OpenPrinter(string n, out IntPtr h, IntPtr d);
    [DllImport("winspool.drv", SetLastError = true)] static extern bool ClosePrinter(IntPtr h);
    [DllImport("winspool.drv", EntryPoint = "StartDocPrinterW", SetLastError = true, CharSet = CharSet.Unicode)] static extern bool StartDocPrinter(IntPtr h, int level, [In] DOCINFO di);
    [DllImport("winspool.drv", SetLastError = true)] static extern bool EndDocPrinter(IntPtr h);
    [DllImport("winspool.drv", SetLastError = true)] static extern bool StartPagePrinter(IntPtr h);
    [DllImport("winspool.drv", SetLastError = true)] static extern bool EndPagePrinter(IntPtr h);
    [DllImport("winspool.drv", SetLastError = true)] static extern bool WritePrinter(IntPtr h, byte[] b, int c, out int w);
    // Devuelve null si salió bien o el texto del error
    public static string Send(string printer, byte[] data) {
        IntPtr h;
        if (!OpenPrinter(printer, out h, IntPtr.Zero)) return "No se pudo abrir la impresora (error " + Marshal.GetLastWin32Error() + ")";
        try {
            DOCINFO di = new DOCINFO(); di.pDocName = "Comanda RestoWeb"; di.pDataType = "RAW";
            if (!StartDocPrinter(h, 1, di)) return "StartDoc falló (error " + Marshal.GetLastWin32Error() + ")";
            try {
                if (!StartPagePrinter(h)) return "StartPage falló (error " + Marshal.GetLastWin32Error() + ")";
                int w;
                bool ok = WritePrinter(h, data, data.Length, out w);
                EndPagePrinter(h);
                if (!ok || w != data.Length) return "No se pudo escribir en la impresora (error " + Marshal.GetLastWin32Error() + ")";
            } finally { EndDocPrinter(h); }
        } finally { ClosePrinter(h); }
        return null;
    }
}
"@

$cfgPath = Join-Path $dir 'config.json'
if (-not (Test-Path $cfgPath)) { Log 'Falta config.json'; exit 1 }
$cfg = Get-Content $cfgPath -Raw | ConvertFrom-Json
$base = ([string]$cfg.url).TrimEnd('/') + '/'

function Impresoras {
    try { return @(Get-CimInstance Win32_Printer | ForEach-Object { $_.Name }) } catch { Log "No se pudo listar impresoras: $_"; return @() }
}

function PostJson($accion, $cuerpo) {
    $cuerpo['token'] = $cfg.token
    return Invoke-RestMethod -Method Post -Uri ($base + $accion) -Body $cuerpo -TimeoutSec 20
}

function ImprimirRed($ip, $puerto, [byte[]]$bytes) {
    $c = New-Object System.Net.Sockets.TcpClient
    try {
        $t = $c.BeginConnect($ip, [int]$puerto, $null, $null)
        if (-not $t.AsyncWaitHandle.WaitOne(3000)) { return "No se pudo conectar a ${ip}:${puerto}" }
        $c.EndConnect($t)
        $s = $c.GetStream(); $s.Write($bytes, 0, $bytes.Length); $s.Flush()
        return $null
    } catch { return "Error de red ${ip}:${puerto}: $_" } finally { $c.Close() }
}

Log 'Agente iniciado'
$ultimaLista = 0; $nombres = @()
while ($true) {
    try {
        if (((Get-Date) - [datetime]'1970-01-01').TotalSeconds - $ultimaLista -gt 60 -or -not $nombres.Count) {
            $nombres = Impresoras; $ultimaLista = ((Get-Date) - [datetime]'1970-01-01').TotalSeconds
        }
        $r = PostJson 'pendientes' @{ impresoras = (ConvertTo-Json -InputObject @($nombres) -Compress) }
        foreach ($t in @($r.trabajos)) {
            if (-not $t) { continue }
            $bytes = [Convert]::FromBase64String([string]$t.datos)
            $err = $null
            $nombre = $nombres | Where-Object { $_ -ieq $t.impresora } | Select-Object -First 1
            if ($t.impresora -and $nombre) { $err = [RawPrint]::Send($nombre, $bytes) }
            elseif ($t.ip -and [int]$t.puerto -gt 1) { $err = ImprimirRed $t.ip $t.puerto $bytes }
            else { $err = "Esta PC no tiene la impresora '$($t.impresora)'" }
            if ($err) { Log "Trabajo $($t.id) ($($t.destino)): $err" } else { Log "Trabajo $($t.id) impreso en $($t.destino)" }
            try { PostJson 'resultado' @{ id = $t.id; ok = $(if ($err) { 0 } else { 1 }); error = [string]$err } | Out-Null } catch { Log "No se pudo informar el resultado: $_" }
        }
    } catch {
        Log "Sin conexión con el sistema: $($_.Exception.Message)"
        Start-Sleep -Seconds 8
    }
    Start-Sleep -Seconds 2
}
