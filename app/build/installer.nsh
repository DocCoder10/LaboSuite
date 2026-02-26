!include "LogicLib.nsh"
!include "MUI2.nsh"
!include "WinMessages.nsh"
!include "nsDialogs.nsh"
!include "FileFunc.nsh"

Var setupStructName
Var setupAddress
Var setupHeaderServices
Var setupPhone
Var setupEmail
Var setupLogoPath

Var setupInputStructName
Var setupInputAddress
Var setupInputHeaderServices
Var setupInputPhone
Var setupInputEmail
Var setupInputLogoPath
Var setupButtonBrowseLogo

!macro customInit
  StrCpy $setupStructName ""
  StrCpy $setupAddress ""
  StrCpy $setupHeaderServices "Analyses medicales - Medecine Generale - Medecine specialisee"
  StrCpy $setupPhone "+223-00-00-00-00"
  StrCpy $setupEmail ""
  StrCpy $setupLogoPath ""
!macroend

!macro customPageAfterChangeDir
  Page Custom SetupIdentityPageCreate SetupIdentityPageLeave
!macroend

!macro customInstall
  CreateDirectory "$INSTDIR\resources\installer"

  SetOutPath "$INSTDIR\resources\installer"
  File "/oname=default-logo.svg" "build\default-logo.svg"

  StrCpy $0 "$INSTDIR\resources\installer\default-logo.svg"

  ${If} "$setupLogoPath" != ""
    ${GetFileExt} "$setupLogoPath" $1
    ${If} "$1" == ""
      StrCpy $1 "png"
    ${EndIf}

    StrCpy $2 "$INSTDIR\resources\installer\setup-logo.$1"
    Delete "$2"
    CopyFiles /SILENT "$setupLogoPath" "$2"
    ${If} ${FileExists} "$2"
      StrCpy $0 "$2"
    ${EndIf}
  ${EndIf}

  StrCpy $3 "$INSTDIR\resources\installer\profile.ini"
  Delete "$3"
  WriteINIStr "$3" "meta" "profile_version" "1"
  WriteINIStr "$3" "meta" "developer" "DocCoder10"
  WriteINIStr "$3" "identity" "name" "$setupStructName"
  WriteINIStr "$3" "identity" "address" "$setupAddress"
  WriteINIStr "$3" "identity" "header_services" "$setupHeaderServices"
  WriteINIStr "$3" "identity" "phone" "$setupPhone"
  WriteINIStr "$3" "identity" "email" "$setupEmail"
  WriteINIStr "$3" "identity" "logo_path" "$0"
!macroend

Function SetupIdentityPageCreate
  !insertmacro MUI_HEADER_TEXT "Configuration initiale (obligatoire)" "Ces infos seront appliquees automatiquement au premier lancement."

  nsDialogs::Create 1018
  Pop $0
  ${If} $0 == error
    Abort
  ${EndIf}

  ${NSD_CreateLabel} 0 0u 100% 10u "Developpe par DocCoder10"
  Pop $0

  ${NSD_CreateLabel} 0 14u 100% 10u "Nom de la structure *"
  Pop $0
  ${NSD_CreateText} 0 24u 100% 12u "$setupStructName"
  Pop $setupInputStructName

  ${NSD_CreateLabel} 0 40u 100% 10u "Adresse *"
  Pop $0
  ${NSD_CreateText} 0 50u 100% 12u "$setupAddress"
  Pop $setupInputAddress

  ${NSD_CreateLabel} 0 66u 100% 10u "Nom entete / services (optionnel)"
  Pop $0
  ${NSD_CreateText} 0 76u 100% 12u "$setupHeaderServices"
  Pop $setupInputHeaderServices

  ${NSD_CreateLabel} 0 92u 100% 10u "Telephone (optionnel)"
  Pop $0
  ${NSD_CreateText} 0 102u 100% 12u "$setupPhone"
  Pop $setupInputPhone

  ${NSD_CreateLabel} 0 118u 100% 10u "Email (optionnel)"
  Pop $0
  ${NSD_CreateText} 0 128u 100% 12u "$setupEmail"
  Pop $setupInputEmail

  ${NSD_CreateLabel} 0 144u 100% 28u "Logo (optionnel). Recommande: PNG/SVG transparent, ratio horizontal 3:1, ideal 1200x400 px (min 900x300). Si autre format, l'application adapte automatiquement."
  Pop $0
  ${NSD_CreateText} 0 172u 76% 12u "$setupLogoPath"
  Pop $setupInputLogoPath
  SendMessage $setupInputLogoPath ${EM_SETREADONLY} 1 0

  ${NSD_CreateButton} 78% 172u 22% 12u "Parcourir..."
  Pop $setupButtonBrowseLogo
  ${NSD_OnClick} $setupButtonBrowseLogo SetupIdentityBrowseLogo

  nsDialogs::Show
FunctionEnd

Function SetupIdentityBrowseLogo
  nsDialogs::SelectFileDialog open "$setupLogoPath" "Images|*.png;*.jpg;*.jpeg;*.webp;*.svg;*.bmp;*.ico|Tous les fichiers|*.*"
  Pop $0

  ${If} $0 == error
    Return
  ${EndIf}

  StrCpy $setupLogoPath "$0"
  ${NSD_SetText} $setupInputLogoPath "$setupLogoPath"
FunctionEnd

Function SetupIdentityPageLeave
  ${NSD_GetText} $setupInputStructName $setupStructName
  ${NSD_GetText} $setupInputAddress $setupAddress
  ${NSD_GetText} $setupInputHeaderServices $setupHeaderServices
  ${NSD_GetText} $setupInputPhone $setupPhone
  ${NSD_GetText} $setupInputEmail $setupEmail
  ${NSD_GetText} $setupInputLogoPath $setupLogoPath

  ${If} "$setupStructName" == ""
    MessageBox MB_ICONEXCLAMATION|MB_OK "Le nom de la structure est obligatoire."
    Abort
  ${EndIf}

  ${If} "$setupAddress" == ""
    MessageBox MB_ICONEXCLAMATION|MB_OK "L'adresse est obligatoire."
    Abort
  ${EndIf}

  ${If} "$setupHeaderServices" == ""
    StrCpy $setupHeaderServices "Analyses medicales - Medecine Generale - Medecine specialisee"
  ${EndIf}

  ${If} "$setupPhone" == ""
    StrCpy $setupPhone "+223-00-00-00-00"
  ${EndIf}
FunctionEnd
