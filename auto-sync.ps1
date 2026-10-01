# Script PowerShell pour synchroniser automatiquement les modifications avec GitHub
# Usage :
#   .\auto-sync.ps1          -> commit et push immediats
#   .\auto-sync.ps1 -Watch   -> surveille les changements de fichiers en continu

param(
    [switch]$Watch,
    [int]$IntervalSeconds = 30
)

function Sync-Git {
    param([string]$Message = "")
    $status = git status --porcelain
    if ($status) {
        Write-Host "Changements detectes. Envoi vers GitHub..." -ForegroundColor Cyan
        if (-not $Message) {
            $Message = "Auto-sync : mise a jour du $(Get-Date -Format 'dd/MM/yyyy HH:mm:ss')"
        }
        git add -A
        git commit -m $Message
        git push origin main
        if ($LASTEXITCODE -eq 0) {
            Write-Host "Modifications envoyees avec succes sur GitHub !" -ForegroundColor Green
        } else {
            Write-Host "Erreur lors du git push." -ForegroundColor Red
        }
    } else {
        Write-Host "Aucune modification a synchroniser." -ForegroundColor Gray
    }
}

if ($Watch) {
    Write-Host "Mode surveillance active (verification toutes les $IntervalSeconds s). Appuyez sur Ctrl+C pour arreter." -ForegroundColor Yellow
    while ($true) {
        Sync-Git
        Start-Sleep -Seconds $IntervalSeconds
    }
} else {
    Sync-Git
}
