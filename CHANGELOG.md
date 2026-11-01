# Yii Queue Change Log

## 1.0.0 under development

- Initial release.
- Chg #350: Replace `queue:run`, `queue:listen`, and `queue:listen-all` with `queue:consume` using `--once` and `--all` options (@samdark)
- Bug #347: `SignalLoop` installs signal handlers only while messages are consumed and restores the previous ones afterwards, instead of replacing them when it is created (@samdark)
