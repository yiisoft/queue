# Yii Queue Change Log

## 1.0.0 under development

- Initial release.
- Bug #346: `QueueConsumer` checks the loop before processing a message instead of after, so a message processed just before the loop stops is counted and is not handled again (@samdark)
