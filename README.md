# 🛡️ RCaptcha - PocketMine Map Captcha Verification Plugin

![PocketMine-MP API 5](https://img.shields.io/badge/PocketMine--MP-API%205-blue)
![Captcha Protection](https://img.shields.io/badge/Captcha-Enabled-green)
![Status](https://img.shields.io/badge/Status-Stable-brightgreen)

## 📌 Overview

**RCaptcha** is a plugin for PocketMine-MP (API 5) that enhances server security by requiring players to complete a **map captcha** when joining the server. It’s especially useful for preventing bots by ensuring only human players can interact or use protected commands.

---

## ⚙️ Installation

1. Download and place the plugin `.phar` file into your `plugins/` directory from [releases](https://github.com/xRookieFight/RCaptcha/releases).
2. Restart your server.
3. The plugin will automatically generate `config.yml` and `messages.yml`.
4. Customize the configuration as needed and restart the server again.

---

## ⚙️ Dependencies
- [ImageOnMap](https://github.com/CzechPMDevs/ImageOnMap/tree/main) by [CzechPMDevs](https://github.com/CzechPMDevs)
---

## 🛠️ Configuration Files

### `config.yml`

```yaml
captcha-settings:
  captcha-protected-commands:
    - 'op'
  held-slot: 1 # 1 = first hotbar slot, -1 = offhand
  mode: FIRSTJOIN # FIRSTJOIN, ALL, NONE
  op-override: false
  permission-override: false
  disable-damage: true
  disable-interact: true
  blindness-effect: true
```

### `messages.yml`

```yaml
fail: '§7You failed the Captcha'
join: '§7Please type the text on the map in order to verify you''re a human being'
success: '§7You completed the Captcha'
blocked: '§cYou cannot do that operation.'
