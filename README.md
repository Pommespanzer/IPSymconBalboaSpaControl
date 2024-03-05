# IPSymconBalboaSpaControl

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-6+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguartion)
6. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Dieses Modul ermöglicht die Steuerung von Whirlpools mit einer verbauten Steuereinheit der Firma [Balboa](https://balboawater.com/).  
Zur Steuerung ist ein verbautes und eingerichtetes WLAN-Modul erforderlich und der Whirlpool muss über die [Spa Control](https://apps.apple.com/de/app/spa-control-bwa/id469882268) App mit der Balboa-Cloud verbunden sein.  

Das Modul nutzt die API von Balboa, die jedoch von Balboa selbst nicht öffentlich dokumentiert wurde.
Manche Funktionen können sich daher - je nach Whirlpool-Modell und Ausstattung - anders verhalten.

### Funktionsumfang

- Jet-Pumpen ein- und ausschalten (Pumpe 1 und 2)
- Blower ein- und ausschalten (Blower 1)
- Licht ein- und ausschalten (Licht 1 und Licht 2)
- Aktuelle Temperatur auslesen
- Gewünschte Temperatur einstellen
- Aktueller Filtermodus anzeigen
- Heizstatus (Heizung aktiv)
- Temperatur Range einstellbar (Ready oder Rest)
- Heizmodus einstellbar (High Range or Low Range)
- 12/24 Stunden Zeitformat auslesen
- Uhrzeit auslesen
- WiFi Verbindungsstatus auslesen

#### Eventuell geplant in Zukunft

 - Uhrzeit einstellen
 - Filterzyklen einstellen (Zyklus 1 und 2)

## 2. Voraussetzungen

1. IP-Symcon ab Version 6
2. Ein Whirlpool mit einer Balboa-Steuerung und WLAN-Modul
3. Whirlpool ist über die [Spa Control App](https://apps.apple.com/de/app/spa-control-bwa/id469882268) in "Cloud Connect" eingerichtet

## 3. Installation

### Modul installieren

1. IP-Symcon Webconsole öffnen
2. Das Modul ModulControl öffnen
3. Das Modul "Balboa Spa Control" suchen und installieren

### Einrichtung in IP-Symcon

1. Neue Instanz unter I/O-Instanzen hinzufügen: `BalboaSpaControlIO`
2. In der Instanz `BalboaSpaControlIO`: Anmeldedaten eingeben und speichern
3. Im Objektbaum eine Instanz hinzufügen: `BalboaSpaControlDevice`
4. Die neue Instanz `BalboaSpaControlDevice` öffnen und auf "Gateway ändern" klicken
5. Die `BalboaSpaControlIO` auswählen und speichern

## 4. Funktionsreferenz

`BalboaSpaControl_SetUpdateIntervall(integer $InstanceID, int $Seconds)`<br>
Ändert das Aktualisierungsintervall

`BalboaSpaControl_SetPump(integer $InstanceID, int $PumpNo, string $Action = null)`<br>
Pumpe steuern.  
`$PumpNo`: `1`, `2`  
`$Action`: `on`, `off`

`BalboaSpaControl_TogglePump(integer $InstanceID, int $PumpNo)`<br>
Pumpe anschalten, wenn ausgeschaltet und umgekehrt  
`$PumpNo`: `1`, `2`  

`BalboaSpaControl_SetBlower(integer $InstanceID, string $Action = null)`<br>
Luftdüsen/Blower steuern.  
`$Action`: `on`, `off`

`BalboaSpaControl_ToggleBlower(integer $InstanceID)`<br>
Luftdüsen/Blower anschalten, wenn ausgeschaltet und umgekehrt

`BalboaSpaControl_SetLight(integer $InstanceID, int $LightNo, string $Action = null)`<br>
Licht steuern. Erlaubte Werte: `on`, `off`  
`$LightNo`: `1` oder `2`  
`$Action`: `on`, `off`

`BalboaSpaControl_ToggleLight(integer $InstanceID, int $LightNo)`<br>
Licht anschalten, wenn ausgeschaltet und umgekehrt  
`$LightNo`: `1` oder `2`

`BalboaSpaControl_SetAux(integer $InstanceID, int $AuxNo, string $Action = null)`<br>
AUX steuern. Erlaubte Werte: `on`, `off`  
`$AuxNo`: `1` oder `2`  
`$Action`: `on`, `off`

`BalboaSpaControl_ToggleAux(integer $InstanceID, int $AuxNo)`<br>
AUX anschalten, wenn ausgeschaltet und umgekehrt  
`$AuxNo`: `1` oder `2`

`BalboaSpaControl_SetHeatMode(integer $InstanceID, string $Mode)`<br>
Heizungsmodus ändern.  
`$Mode`: `rest`, `ready`

`BalboaSpaControl_SetTemperatureRange(integer $InstanceID, string $Range)`<br>
Temperaturbereich ändern.  
`$Range`: `low`, `high`

`BalboaSpaControl_SetTargetTemperature(integer $InstanceID, string $Temperature)`<br>
Gewünschte Temperatur einstellen. Erlaubte Werte sind abhängig vom Temperaturbereich:  

`BalboaSpaControl_GetPanelData($InstanceID)`<br>
Liefert alle Daten des Whirlpool-Panels. Liefert entweder `PanelUpdate` oder `false`

`BalboaSpaControl_GetDeviceConfiguration($InstanceID)`<br>
Liefert die Gerätekonfiguration. Liefert entweder `DeviceConfiguration` oder `false`

#### Erlaubte Temperaturen  

| Einheit    | Temperaturbereich | Min. | Max. |
|:-----------|:------------------|:-----|:-----|
| Celsius    | Low               | 10   | 37   |
| Celsius    | High              | 26.5 | 40   |
| Fahrenheit | Low               | 50   | 99   | 
| Fahrenheit | High              | 80   | 104  | 

#### PanelUpdate

| Funktion                    | Datentyp     | Beschreibung                                             |
|:----------------------------|:-------------|:---------------------------------------------------------|
| getByteData()               | array, mixed | Liefert alle Rohdaten, die von der API übermittelt wurden |
| getHeatMode()               | string       | Aktueller Heizmodus                                      | 
| isHeating()                 | bool         | Heizung aktiv                                            | 
| getTemperatureRange()       | string       | Eingestellter Temperaturbereich                          |
| isTemperatureScaleCelsius() | bool         | Ob die Temperatureinheit Celsius ist                     |
| getCurrentTemperature()     | float        | Aktuelle Temperatur                                      | 
| getTargetTemperature()      | float        | Eingestellte Zieltemperatur                              | 
| getFilterMode()             | string       | Aktueller Filtermodus                                    | 
| getHour()                   | string       | Aktuelle Stunde                                          | 
| getMinute()                 | string       | Aktuelle Minute                                          | 
| is24HourFormat()            | bool         | Ost das Zeitformat als 24H-Format eingestellt ist        | 
| isMisterOn()                | bool         | Mister an                                                | 
| isLight1On()                | bool         | Licht 1 an                                               | 
| isLight2On()                | bool         | Licht 2 an                                               | 
| isAux1On()                  | bool         | Aux 1 an                                                 | 
| isAux2On()                  | bool         | Aux 2 an                                                 | 
| getPump1Status()            | string       | Status Pumpe 1                                           | 
| getPump2Status()            | string       | Status Pumpe 2                                           | 
| getPump3Status()            | string       | Status Pumpe 3                                           | 
| getPump4Status()            | string       | Status Pumpe 4                                           | 
| getPump5Status()            | string       | Status Pumpe 5                                           | 
| getPump6Status()            | string       | Status Pumpe 6                                           | 
| getBlowerStatus()            | string       | Luftdüsen an                                             | 
| getPumpStatus()            | string       | Status Zirkulationspumpe                                 | 
| getWifiStatus()            | string       | Status WiFi Verbindung des Moduls                        | 

#### DeviceConfiguration

| Funktion      | Datentyp     | Beschreibung                                              |
|:--------------|:-------------|:----------------------------------------------------------|
| getByteData() | array, mixed | Liefert alle Rohdaten, die von der API übermittelt wurden |
| hasPump0()    | bool         | Zirkulationspumpe vorhanden                               |
| hasPump1()    | bool         | Pumpe 1 vorhanden                                         |
| hasPump2()    | bool         | Pumpe 2 vorhanden                                         |
| hasPump3()    | bool         | Pumpe 3 vorhanden                                         |
| hasPump4()    | bool         | Pumpe 4 vorhanden                                         |
| hasPump5()    | bool         | Pumpe 5 vorhanden                                         |
| hasPump6()    | bool         | Pumpe 6 vorhanden                                         |
| hasLight1()   | bool         | Licht 1 vorhanden                                         |
| hasLight2()   | bool         | Licht 2 vorhanden                                         |
| hasAux1()     | bool         | Aux 1 vorhanden                                           |
| hasAux2()     | bool         | Aux 2 vorhanden                                           |
| hasBlower()   | bool         | Luftdüsen vorhanden                                       |
| hasMister()   | bool         | Mister vorhanden                                          |

## 5. Konfiguration

### Variablen (BalboaSpaControlIO)

| Eigenschaft | Typ     | Standardwert | Funktion          |
|:------------| :------ | :----------- |:------------------|
| username    | string  |              | Benutzername      |
| password    | string  |              | Passwort          |

### Variablen (BalboaSpaControlDevice)

| Eigenschaft    | Typ     | Standardwert | Funktion                    |
|:---------------|:--------|:-------------|:----------------------------|
| UpdateInterval | integer | 30           | Update Interval in Sekunden |

## 6. Versions-Historie

- 0.2 @ 05.03.2024
    - Neue Funktion `BalboaSpaControl_GetPanelData($InstanceID): PanelUpdate` hinzugefügt
    - Neue Funktion `BalboaSpaControl_GetDeviceConfiguration($InstanceID): DeviceConfiguration` hinzugefügt
    - Fehler behoben, dass Cloud Connect seltsame Werte zurückliefert, wenn das WLAN Modul Verbindungsprobleme hat
    - Übersetzungen hinzugefügt

- 0.1 @ 03.03.2024
    - Initiale Version  