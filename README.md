# Cloud Poodll Assignment Feedback for Moodle

**Adds audio, video, screencast and AI-corrected text feedback to native Moodle Assignment
grading.**

Instead of typing feedback, the teacher can record an audio or video response, record a
screencast walkthrough, or let Cloud Poodll generate AI-powered grammar corrections on the
student's own submission text. Feedback recordings are stored in the Poodll cloud, so course
backups stay small, and it works alongside the companion Cloud Poodll Assignment Submission
plugin.

- **Plugin:** `assignfeedback_cloudpoodll` (assignment feedback subplugin)
- **Maintainer:** Justin Hunt — poodllsupport@gmail.com
- **Documentation:** https://support.poodll.com
- **License:** GNU GPL v3 or later

---

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Site configuration](#site-configuration)
- [The five feedback types](#the-five-feedback-types)
- [Recorder skins and playback](#recorder-skins-and-playback)
- [Privacy](#privacy)
- [Support](#support)

---

## Requirements

| | |
|---|---|
| Moodle |  4.3 or later (`$plugin->requires = 2023100900`) |
| PHP | 8.1+ (PHP 8.4 supported) |
| Database | MySQL / MariaDB / PostgreSQL |
| Cloud Poodll account | **Required.** An API user and secret from https://poodll.com |

Cloud Poodll relies on the Cloud Poodll service for recording, storage, transcoding, speech
transcription and AI grammar correction. Without API credentials the feedback type will not
function. See [Cloud Poodll API secret](https://support.poodll.com/support/solutions/articles/19000083076-cloud-poodll-api-secret)
for how to obtain them.

## Installation

1. Copy the plugin folder to `mod/assign/feedback/cloudpoodll` in your Moodle code root (on
   Moodle 5.0+ this is `public/mod/assign/feedback/cloudpoodll`).
2. Visit **Site administration → Notifications** and complete the upgrade.
3. Enter your Cloud Poodll API user and secret at
   **Site administration → Plugins → Assignment feedback plugins → Cloud Poodll Feedback**.

## Site configuration

Settings live under **Site administration → Plugins → Assignment feedback plugins → Cloud
Poodll Feedback**:

- **API user / API secret**
- **AWS region**  The AWS region processing and data storage take place in, 
- **Cloud Poodll server** The default is fine. Users with AWS region Ningxia in China should use cloud.poodll.cn
- **Enabled by default** — turn this feedback type on for all new assignments automatically.
- **Default recorder type** — see the five feedback types below.
- **Corrections language** — the default language used for AI grammar correction of a student's
  submission text/transcript.
- Separate **audio** and **video** settings groups controlling player appearance (single view
  and list view: inline, lightbox, link, or hidden) and player size.
- **Transcription** and **transcoding** (to mp3/mp4) can each be enabled independently.
- **Fallback behaviour** for browsers without HTML5 recording support: an upload screen, an
  iOS-specific upload screen, or a warning.
- Expiry days for recordings, and a custom name for the feedback field.

## The four feedback types

The teacher's recorder can be set to one specific type, or to **Free**, which lets the teacher
choose per feedback:

| Type | What it gives the student |
|---|---|
| **Audio** | A recorded audio response from the teacher. |
| **Video** | A recorded video response from the teacher. |
| **Text Feedback** | Written feedback, entered directly. |
| **Corrections Feedback** | Cloud Poodll runs the student's own submitted text (or speech transcript) through AI grammar correction and shows the marked-up corrections as feedback — no recording required from the teacher. |

Audio and video feedback can also be transcribed, with the transcript (and captions) shown
alongside the recording.


## Privacy

This plugin stores personal data: feedback recordings and, if enabled, transcripts. Recordings
are stored via Cloud Poodll (AWS S3), and the hash of the Moodle username appears in recording
URLs. The plugin implements the Moodle Privacy API for export and deletion.

## Support

- Documentation and how-tos: https://support.poodll.com
- Account and subscriptions: https://member.poodll.com
- Contact: poodllsupport@gmail.com

## License

Copyright Justin Hunt / Poodll. Licensed under the
[GNU GPL v3 or later](http://www.gnu.org/copyleft/gpl.html).
