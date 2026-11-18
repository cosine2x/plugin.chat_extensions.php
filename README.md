# Cosine's PHP 8 Chat Extensions

This plugin replaces the legacy `plugin.rasp_chat.php` from Xymph's Automatic Server Control (XASECO for short).

## Features
- **Targeted Social Commands:** `/hug`, `/highfive`, `/slap`, `/poke`
- **Optional Social Commands:** `/n1`, `/gg`, `/thanks`, `/wave`
- **Emotes:** `/dance`, `/shrug`, `/facepalm`
- **Mini-Games:**
  - `/roll [max]` (Rolls a die, default 100)
  - `/flip` (Flips a coin)
  - `/8ball <question>` (Consults the magic 8-ball)

## Dependencies
- **XASECO** with PHP 8 compatibility (available at [Codeberg - hanouta/xaseco](https://codeberg.org/hanouta/xaseco)).

## Installation
1. Download the latest version of the plugin from the [Releases page](https://github.com/cosine2x/plugin.chat_extensions.php/releases).
   
2. Copy `plugin.chat_extensions.php` into your server's plugins directory (usually `/xaseco/plugins/`).
   
3. Open your `plugins.xml` configuration file (usually `/xaseco/plugins.xml`) and add the following line:
   ```xml
   <plugin>plugin.chat_extensions.php</plugin>
   ```
   Make sure to disable the legacy `rasp_chat` plugin using one of these methods:
   - **Comment it out** in `plugins.xml`:
     ```xml
     <!-- <plugin>plugin.rasp_chat.php</plugin> -->
     ```
   - **Remove it completely** by deleting its line from `plugins.xml` and deleting `plugin.rasp_chat.php` from `/xaseco/plugins/`.
    
4. Restart XASECO on your server.

# Acknowledgments
Xymph's Automatic Server Control, XASECO, available @ [xaseco.org](https://www.xaseco.org/)

Hanouta's XASECO Update for PHP 8 compatibility, available @ [codeberg.org](https://codeberg.org/hanouta/xaseco/)

## License
This project is licensed under the **GNU General Public License v3.0 (GPLv3)**. See the [LICENSE](LICENSE) file for details.
