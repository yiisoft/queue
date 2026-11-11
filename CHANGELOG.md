# Yii Queue Change Log

## 1.0.0 under development

- Enh #355: Report the terminal program status (`OSC 7501`) for `queue:consume`, so terminals that implement the Program Status Protocol can show what the command is doing; reports go out only for decorated, non-quiet, interactive outputs and are disabled by `YII_TERMINAL_STATUS=off` (`YII_QUEUE_STATUS=off` is accepted as a compatibility alias) (@samdark)
- Initial release.