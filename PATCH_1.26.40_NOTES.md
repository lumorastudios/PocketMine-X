# Patch: dukungan Minecraft: Bedrock Edition 1.26.40

Patch tidak resmi ini dibuat karena upstream `pmmp/PocketMine-MP` dan
`pmmp/BedrockProtocol` sudah **diarsipkan oleh pemiliknya pada 9 Juli 2026**
(proyek PocketMine-MP resmi berhenti / "shut down", lihat pengumuman di
https://pmmp.io/ dan https://www.anthropic.com — err, maksudnya
https://github.com/pmmp/PocketMine-MP). Minecraft 1.26.40 baru rilis 4
Agustus 2026, setelah upstream berhenti, jadi tidak akan pernah ada rilis
resmi pmmp untuk versi ini. Base yang dipakai di sini: PocketMine-MP 5.44.3
(rilis resmi terakhir, target 1.26.30) + fork custom multiversion milik
proyek ini, digabung dengan `bedrock-protocol` versi `master` dari fork
altayofficial (sudah menyasar protokol 2168 / `1.26.40`).

## Yang sudah dikerjakan

1. **`vendor/pocketmine/bedrock-protocol` diarahkan ke build 1.26.40.**
   Source BedrockProtocol yang di-upload ditaruh di `../deps/bedrock-protocol`
   (sejajar dengan folder PocketMine-MP ini — struktur yang sama dengan
   `install-local-protocol.sh` bawaan pmmp). `composer.json` sudah diubah
   untuk memakainya lewat path repository. Nama paket & dependency NBT-nya
   saya benerin (lihat bagian "Detail teknis" di bawah) supaya tidak bentrok
   sama dependency pmmp yang lain.

2. **Empat titik kode yang manggil `Packet::create()` dengan signature versi
   1.26.30 sudah diperbaiki**, karena signature-nya berubah di 1.26.40. Ini
   saya temukan dengan cara membandingkan setiap pemanggilan `::create()` di
   seluruh source PocketMine-MP terhadap signature aktual di source
   BedrockProtocol yang baru (bukan tebakan):

   - `AnvilDamagePacket` — field `damageAmount` sudah dihapus total dari
     packet ini. (`src/multiversion/legacy/codec/AnvilDamagePacketLegacyCodec.php`)
   - `TransferPacket` — parameter baru `?GatheringJoinInfo $gatheringJoinInfo`
     (di-set `null`, PM tidak implementasi fitur Xbox "Gathering" ini).
     (`src/network/mcpe/NetworkSession.php`)
   - `LevelChunkPacket` — parameter baru `?int $clientRequestSubChunkLimit`
     disisipkan di tengah, dan `usedBlobHashes` sekarang wajib `array` (bukan
     nullable lagi). (`src/network/mcpe/ChunkRequestTask.php`)
   - `StartGamePacket` — satu parameter boolean yang dulu ada tepat setelah
     `networkPermissions` sudah dihapus di versi 1.26.40.
     (`src/network/mcpe/handler/PreSpawnPacketHandler.php`)

3. **Lapisan multiversion (dukungan client lama 1.26.0/1.26.10/1.26.20)
   dicek ulang secara menyeluruh** — 28 packet dengan legacy codec, semua
   property & signature `::create()`-nya dibandingkan terhadap class
   packet yang baru. Selain bug `AnvilDamagePacket` di atas (yang juga
   dipakai lapisan ini), semuanya masih kompatibel apa adanya. Jadi client
   1.26.0/1.26.10/1.26.20 tetap bisa connect seperti sebelumnya.

4. **Seluruh handler paket masuk (`InGamePacketHandler`, dll — 30 method
   `handleXxx()`) dicek** terhadap property/getter yang tersedia di class
   packet baru. Tidak ada yang perlu diubah di sini.

## Yang BELUM dikerjakan / keterbatasan yang perlu diketahui

- **`bedrock-data`, `bedrock-block-upgrade-schema`, `bedrock-item-upgrade-schema`
  TIDAK di-update** (tidak di-upload, dan tidak bisa saya buat dari nol —
  butuh dump asli dari game client/BDS). Artinya block & item BARU di
  1.26.40 (misalnya varian tangga/slab wool warna-warni, pouf/bantal duduk,
  loot Abandoned Camp, dll) belum punya definisi di sisi server. Block/item
  lama tetap berfungsi normal. `blockPaletteChecksum` sengaja dikirim `0`
  oleh PocketMine-MP sendiri jadi client tidak akan menolak koneksi karena
  ini — cuma block/item barunya saja yang belum "dikenal" server.
- **Client 1.26.30 TIDAK ditambahkan ke daftar versi lama yang didukung.**
  Setelah patch ini, `CURRENT_PROTOCOL` menjadi milik 1.26.40, dan yang
  didukung sebagai "versi lama" cuma tetap 1.26.0/1.26.10/1.26.20 seperti
  sebelumnya. Kalau mau tambah 1.26.30 juga, perlu dibuatkan legacy codec
  baru khusus untuk 4 packet di atas (saya sudah tahu persis bedanya, jadi
  ini bisa dikerjakan lagi kalau diperlukan — tinggal bilang).
- Saya tidak punya akses PHP/Composer/jaringan di sandbox ini, jadi
  perubahan ini **belum pernah benar-benar dijalankan/dites** (`composer
  install`, `phpstan`, atau start server beneran). Semua verifikasi di atas
  dilakukan lewat pembacaan source & pencocokan signature secara manual +
  script, bukan lewat compiler/test run sungguhan.

## Cara pakai

1. Folder `deps/bedrock-protocol/` sudah ada DI DALAM folder `PocketMine-MP/`
   ini (bukan sejajar lagi) — supaya bisa langsung di-push sebagai SATU repo
   GitHub tanpa perlu setup repo terpisah.
2. Jalankan:
   ```
   composer install
   ```
   (`composer.json` sudah diarahkan ke `deps/bedrock-protocol` secara relatif
   terhadap folder ini, jadi tidak perlu `install-local-protocol.sh`.)
3. Jalankan seperti biasa (`php PocketMine.php` / `start.sh` sesuai OS).
4. Disarankan jalankan `vendor/bin/phpstan analyse` dulu kalau punya PHP di
   mesin lokal, untuk menangkap kalau ada hal lain yang saya lewatkan.

## Update: migrasi bedrock-data ke 1.26.40 (nyusul setelah patch protokol di atas)

Bedrock-data 1.26.40 yang di-upload ternyata bukan cuma update konten — formatnya
direstrukturisasi total dibanding yang dipakai PMMP 5.44.3 (target 1.26.30):
file `.nbt` sekarang di-**gzip**, dan beberapa file digabung/berubah bentuk
(`canonical_block_states.nbt` → `block_palette.nbt` dibungkus `{blocks:[...]}`,
`required_item_list.json` → `item_palette.json`, direktori `creative/` dan
`recipes/` masing-masing jadi satu file `creative_items.json`/`recipes.json`
dengan skema yang beda total, `biome_definitions.json` → `.nbt`).

**Yang sudah dimigrasi & berfungsi:**
- Block palette (`BlockStateDictionary::loadPaletteFromString`) — baca gzip +
  format baru, tetap kompatibel ke belakang kalau suatu saat dikasih data lama.
- Item palette (`ItemTypeDictionaryFromDataHelper::loadFromString`) — format
  `{"items":[...]}` baru. **Catatan**: `component_nbt` per-item sudah nggak ada
  di file ini lagi (dipindah ke `item_components.nbt` terpisah) — belum
  di-join, jadi semua item pakai NBT komponen kosong seperti item yang memang
  nggak punya komponen khusus.
- Recipes (`CraftingManagerFromDataHelper::makeFromRecipesJson`) — parser baru
  untuk `recipes.json` (shapeless/shaped/potion type/potion container, sesuai
  kode tipe resep asli dari protokol Bedrock). Smithing (transform/trim) masih
  belum diimplementasi — ini juga belum diimplementasi di kode asli PMMP
  sebelumnya (`//TODO: smithing`), jadi bukan regresi.
- Creative inventory (`CreativeInventory`) — parser baru untuk
  `creative_items.json` (groups + items by index). **Catatan**: varian block
  state persis (`block_state_b64`) belum dipakai — item creative pakai block
  state default-nya, bukan varian spesifik dari data.
- `entity_identifiers.nbt` — ditambah dukungan gzip.

**Yang BELUM dimigrasi (fallback aman, nggak bikin crash):**
- `biome_definitions.nbt` — formatnya NBT kompleks (bukan JSON lagi), belum
  ada parser-nya. Server fallback ke daftar biome KOSONG kalau file ini gagal
  di-parse sebagai JSON (server tetap jalan, tapi efek rendering spesifik-biome
  di client mungkin nggak akurat).
- `item_components.nbt`, `block_tags.json`, `camera_presets.nbt`,
  `camera_aim_assist_presets.nbt`, `trim_data.json` — file baru yang memang
  belum ada konsumennya sama sekali di PMMP, jadi belum "rusak", cuma belum
  dimanfaatkan.
- `pocketmine/bedrock-block-upgrade-schema` dan
  `pocketmine/bedrock-item-upgrade-schema` masih pakai versi lama (1.21.110 /
  1.26.20) dari packagist — nggak di-upload, dan resikonya rendah (cuma
  mempengaruhi upgrade world/item super lama).

Source bedrock-data-nya di-vendor di `deps/bedrock-data/` (pola sama seperti
`deps/bedrock-protocol` dan `deps/nbt` — path repository di `composer.json`).

## Detail teknis: kenapa composer.json/nbt diubah

- `deps/bedrock-protocol/composer.json`: nama paket diubah dari
  `altayofficial/bedrock-protocol` jadi `pocketmine/bedrock-protocol` supaya
  cocok dengan key `require` di `composer.json` utama PocketMine-MP lewat
  path repository (composer mencocokkan berdasarkan field `name` di
  composer.json target, bukan nama foldernya).
- Dependency `altayofficial/nbt: dev-stable` diganti ke `pocketmine/nbt:
  ~1.2.0` (yang sudah dipakai PocketMine-MP sendiri), karena keduanya
  menyediakan namespace PHP yang sama (`pocketmine\nbt`) — kalau dua-duanya
  ke-install, autoloader bisa bentrok. Fork NBT altayofficial kemungkinan
  cuma beda dikit (atau malah identik) dari `pocketmine/nbt` resmi; kalau
  ternyata ada API baru yang dibutuhkan dan hilang, error-nya akan langsung
  kelihatan sebagai "Call to undefined method" saat `composer install` /
  server start.
