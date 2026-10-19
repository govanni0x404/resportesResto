@echo off
setlocal
title Caja Web - Crear acceso directo

rem ==========================================================================
rem  Crea en el escritorio el acceso "Caja Web": abre la caja en un perfil propio
rem  de Firefox (o Chrome / Edge) con impresion silenciosa, para que el ticket
rem  salga directo por la impresora predeterminada de Windows.
rem
rem  Uso: doble clic y escribir la direccion del sistema del cliente, o bien
rem       crear_acceso_caja.bat https://cliente.dominio.cl
rem ==========================================================================

echo.
echo  ============================================
echo    CAJA WEB - Acceso con impresion directa
echo  ============================================
echo.

rem ---- Direccion del sistema ----
set "URL=%~1"
if "%URL%"=="" set /p "URL=Direccion del sistema (ej: https://cliente.dominio.cl): "
if "%URL%"=="" (
    echo.
    echo  [ERROR] Debe indicar la direccion del sistema.
    goto :fin
)
if /i not "%URL:~0,4%"=="http" set "URL=https://%URL%"
if "%URL:~-1%"=="/" set "URL=%URL:~0,-1%"
if /i not "%URL:~-5%"=="/Caja" set "URL=%URL%/index.php/Caja"

set "NOMBRE_ACCESO=Caja Web"

rem ---- Navegador: Firefox; si no esta, Chrome; si no, Edge (viene con Windows 10/11) ----
set "NAVEGADOR="
set "NOMBRE_NAV="
for %%R in ("%ProgramFiles%" "%ProgramFiles(x86)%") do (
    if not defined NAVEGADOR if exist "%%~R\Mozilla Firefox\firefox.exe" (
        set "NAVEGADOR=%%~R\Mozilla Firefox\firefox.exe"
        set "NOMBRE_NAV=Mozilla Firefox"
    )
)
for %%R in ("%ProgramFiles%" "%ProgramFiles(x86)%" "%LocalAppData%") do (
    if not defined NAVEGADOR if exist "%%~R\Google\Chrome\Application\chrome.exe" (
        set "NAVEGADOR=%%~R\Google\Chrome\Application\chrome.exe"
        set "NOMBRE_NAV=Google Chrome"
    )
)
for %%R in ("%ProgramFiles(x86)%" "%ProgramFiles%") do (
    if not defined NAVEGADOR if exist "%%~R\Microsoft\Edge\Application\msedge.exe" (
        set "NAVEGADOR=%%~R\Microsoft\Edge\Application\msedge.exe"
        set "NOMBRE_NAV=Microsoft Edge"
    )
)
if not defined NAVEGADOR (
    echo.
    echo  [ERROR] No se encontro Firefox, Chrome ni Edge.
    echo          Instale Firefox y vuelva a ejecutar este archivo.
    goto :fin
)

rem ---- Perfil propio: el navegador normal del equipo sigue imprimiendo con dialogo ----
if "%NOMBRE_NAV%"=="Mozilla Firefox" goto :firefox

set "PERFIL=%LocalAppData%\CajaWeb"
set "ARGS=--kiosk-printing --user-data-dir="%PERFIL%" --start-maximized --no-first-run "%URL%""
rem Sin esta politica, Chrome/Edge usan la ultima impresora elegida y no la predeterminada.
reg add "HKCU\Software\Policies\Google\Chrome" /v PrintPreviewUseSystemDefaultPrinter /t REG_DWORD /d 1 /f >nul
reg add "HKCU\Software\Policies\Microsoft\Edge" /v PrintPreviewUseSystemDefaultPrinter /t REG_DWORD /d 1 /f >nul
goto :accesos

:firefox
rem Firefox: user.js se aplica en cada inicio del perfil. Imprime sin dialogo, en la impresora
rem elegida aqui (o la predeterminada de Windows) y sin encabezados ni pies de pagina.
rem La pagina web no puede elegir impresora: cada acceso queda amarrado a una. Con dos
rem impresoras en el mismo equipo, se ejecuta este archivo una vez por impresora.
call :elegir_impresora
if defined IMPRESORA (
    set "SUFIJO=%IMPRESORA:\=_%"
) else (
    set "SUFIJO="
)
if defined SUFIJO set "SUFIJO=%SUFIJO:/=_%"
if defined SUFIJO set "SUFIJO=%SUFIJO::=_%"
if defined SUFIJO (
    set "PERFIL=%LocalAppData%\CajaWebFirefox-%SUFIJO%"
    set "NOMBRE_ACCESO=Caja Web - %SUFIJO%"
) else (
    set "PERFIL=%LocalAppData%\CajaWebFirefox"
)
rem En user.js la barra invertida de impresoras de red va doble
set "IMPRESORA_JS="
if defined IMPRESORA set "IMPRESORA_JS=%IMPRESORA:\=\\%"
if not exist "%PERFIL%" mkdir "%PERFIL%"
set "USERJS=%PERFIL%\user.js"
> "%USERJS%" echo // Caja Web: impresion directa del ticket
>>"%USERJS%" echo user_pref("print.always_print_silent", true);
>>"%USERJS%" echo user_pref("print.show_print_progress", false);
>>"%USERJS%" echo user_pref("print_printer", "%IMPRESORA_JS%");
>>"%USERJS%" echo user_pref("print.save_print_settings", false);
>>"%USERJS%" echo user_pref("print.print_headerleft", "");
>>"%USERJS%" echo user_pref("print.print_headercenter", "");
>>"%USERJS%" echo user_pref("print.print_headerright", "");
>>"%USERJS%" echo user_pref("print.print_footerleft", "");
>>"%USERJS%" echo user_pref("print.print_footercenter", "");
>>"%USERJS%" echo user_pref("print.print_footerright", "");
>>"%USERJS%" echo user_pref("browser.shell.checkDefaultBrowser", false);
set "ARGS=-no-remote -profile "%PERFIL%" "%URL%""

:accesos
echo.
set "INICIO=N"
set /p "INICIO=Abrir la caja al iniciar Windows? (S/N) [N]: "

rem ---- Crear accesos (PowerShell lee los datos desde variables de entorno) ----
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$ws = New-Object -ComObject WScript.Shell;" ^
  "$destinos = @([Environment]::GetFolderPath('Desktop'));" ^
  "if ($env:INICIO -match '^[sS]') { $destinos += [Environment]::GetFolderPath('Startup') };" ^
  "foreach ($d in $destinos) {" ^
  "  $l = $ws.CreateShortcut((Join-Path $d ($env:NOMBRE_ACCESO + '.lnk')));" ^
  "  $l.TargetPath = $env:NAVEGADOR;" ^
  "  $l.Arguments = $env:ARGS;" ^
  "  $l.WorkingDirectory = Split-Path $env:NAVEGADOR;" ^
  "  $l.IconLocation = $env:NAVEGADOR + ',0';" ^
  "  $l.Description = 'Caja Web con impresion directa';" ^
  "  $l.Save();" ^
  "  Write-Host ('  Creado: ' + (Join-Path $d ($env:NOMBRE_ACCESO + '.lnk')))" ^
  "}"
if errorlevel 1 (
    echo.
    echo  [ERROR] No se pudo crear el acceso directo.
    goto :fin
)

echo.
echo  Acceso    : %NOMBRE_ACCESO%
echo  Navegador : %NOMBRE_NAV%
if defined IMPRESORA echo  Impresora : %IMPRESORA%
echo  Direccion : %URL%
echo.
echo  ------------------------------------------------------------
echo   ANTES DE VENDER (una sola vez):
echo   1. Impresora termica con su driver instalado y marcada como
echo      PREDETERMINADA en Windows: la caja imprime siempre en esa.
echo      Cierre el navegador por completo para que tome el cambio.
echo   2. Abra "Caja Web" desde el escritorio e inicie sesion.
echo   3. Haga una venta de prueba: el ticket debe salir solo.
echo      Si sale en otra impresora, revise cual es la predeterminada.
echo      Si sale cortado, revise el papel en Preferencias de
echo      impresion del driver (80 mm, rollo continuo).
echo   4. Para volver a crear el acceso, ejecute este archivo de nuevo.
echo  ------------------------------------------------------------

:fin
echo.
pause
endlocal
exit /b

rem ---- Lista las impresoras instaladas y deja la elegida en IMPRESORA (vacia = predeterminada) ----
:elegir_impresora
set "IMPRESORA="
echo.
echo  Impresoras instaladas:
echo    0. Predeterminada de Windows
setlocal EnableDelayedExpansion
set /a N=0
for /f "delims=" %%P in ('powershell -NoProfile -Command "Get-CimInstance Win32_Printer | Sort-Object Name | ForEach-Object { $_.Name }"') do (
    set /a N+=1
    set "IMP_!N!=%%P"
    echo    !N!. %%P
)
echo.
set "OPCION=0"
set /p "OPCION=Impresora para esta caja (numero) [0]: "
set "ELEGIDA="
if not "!OPCION!"=="0" if defined IMP_!OPCION! set "ELEGIDA=!IMP_%OPCION%!"
endlocal & set "IMPRESORA=%ELEGIDA%"
goto :eof
