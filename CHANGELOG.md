# Yii Queue Change Log

## 1.0.0 under development

- Initial release.
- Bug #341: Pass the message a handler was registered for to that handler instead of the envelope an adapter wrapped it in, so a handler no longer reads properties off an `IdEnvelope`, `DelayEnvelope` or `FailureEnvelope` and works on empty values; what the envelope carried comes with the message as meta, and consume middleware still receive the envelope itself (@KalimeroMK)
- Bug #347: `SignalLoop` installs signal handlers only while messages are consumed and restores the previous ones afterwards, instead of replacing them when it is created (@samdark)
