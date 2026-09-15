# Running the site

Everything here is done from `/admin` on your own website. Sign in with the
username and password you were given, and change that password the first time
you sign in — the admin will keep nagging you until you do.

---

## Adding a property

1. **Properties → Add a property**
2. Fill in the headline, the monthly rent and the town. Those three are the
   only ones the site insists on; everything else can wait.
3. **Save property.** It goes on the website straight away.
4. On the screen that follows, choose your photographs and press Upload. You
   can select several at once.

A few things worth knowing:

- **Headline** is what people read on the results page. Say what it is and
  roughly where — "Two-bedroom apartment, Walnut Street, Montclair" beats "Lovely apartment".
- **Key features** is one per line. They come out as a bulleted list.
- **Full description** is plain text. Leave a blank line where you want a new
  paragraph. Typing HTML will not do anything — it shows as text.
- **Available from** takes a date like `2026-10-01`, or free text like `Now`.
  It is displayed as `October 1, 2026`.
- **State** starts on NJ, which you can change under Settings if you ever list
  outside New Jersey.
- **Lease term, pet policy, parking, utilities** are the fields renters scan
  first. Fill them in even when the answer is "street parking" or "tenant pays
  all utilities" — a blank simply does not appear on the listing.
- **Feature this property** moves it to the top of the listings and onto the
  home page. Use it for the two or three you most want let.

### Photographs

- JPEG, PNG or WebP. Big photos are resized down automatically, so straight
  off a phone is fine.
- The first photo is the cover image used on the results page. To change it,
  press **Make cover** under a different one.
- **Delete** removes a photo permanently.

Landscape photos work better than portrait — the listing cards are wider than
they are tall.

---

## When a property is let

You have two options, and they do different things.

**Set the letting status to "Let"** (edit the property, Letting status → Let).
The property stays on the site but is hidden from the default search. Anyone
who finds it sees "This property is now let" instead of the inquiry form. Good
for showing the kind of homes you manage.

**Archive it** (bottom of the edit screen). It comes off the website entirely
but nothing is deleted — photos, description and any inquiries about it are all
kept, and **Restore** puts it back exactly as it was. This is the one to use
when a property leaves your portfolio.

Only an archived property can be deleted permanently, so a live listing cannot
vanish with one stray click.

---

## Inquiries

Every message from the website lands in **Inquiries**, and you also get an
email. New ones are highlighted and counted in the sidebar.

Open one to see the full message and the contact details. **Reply by email**
opens your normal email program with their address and the reference already
filled in — replies go from your own mailbox, not from the website.

Set the status as you go (New → Read → Replied → Closed) and use the notes box
for anything you want to remember. Notes are private; the inquirer never sees
them.

**Delete permanently** removes the name, email, phone number and message for
good. That is what to use if someone asks you to delete their data.

---

## Changing the wording

**Settings** covers the things that appear in more than one place:

- Your business name and tagline (header, browser tab, emails)
- The home page heading and introduction
- Your phone number, email, address and opening hours
- Where inquiry alerts are sent
- The two colors the site is built from

**Website text** covers the standalone pages: About, Disclosures, Privacy
policy, Terms of use.

The **Disclosures** page ships as a checklist of prompts about lead paint,
flood risk, Truth in Renting, certificates of occupancy and registration. It is
not finished legal text and should not be published as-is. Have it reviewed,
then rewrite it in your own words. Same rules as a property description — plain text, blank line between
paragraphs.

**Fees and charges** is the public fees page. It ships with placeholder
amounts reading `SET YOUR AMOUNT` and `CONFIRM BEFORE PUBLISHING`. Those are
deliberate — replace every one of them before the site goes live. Edit any row in place, drop a new
one into the dashed box at the bottom, untick **On site** to hide a row without
losing it, or clear a row's title and save to remove it altogether. The
**Order** number decides where each row appears.

Everything saves straight to the live website. There is no separate publish
step.

---

## Adding a map to the contact page

1. Find your address in Google Maps
2. **Share → Embed a map → Copy HTML**
3. From the code it gives you, copy only the address inside `src="..."` — it
   starts `https://www.google.com/maps/embed?`
4. Paste that into **Settings → Google Maps embed link** and save

Only a Google Maps embed address is accepted in that box, so nothing else can
be slipped into your page through it.

---

## Backups

Ask your host for the two things that matter:

- `data/site.sqlite` — every property, inquiry, fee and setting
- `public/uploads/` — the photographs

Most hosts run nightly backups of the whole account, which covers both. It is
worth checking that yours does, and that you can actually restore from one.

---

## If something goes wrong

**A photo will not upload.** It is probably over the size limit, or not a JPEG,
PNG or WebP. The error message says which.

**I am not getting inquiry emails.** Check Settings → "Send inquiry alerts to".
Then check your spam folder. The inquiries themselves are always in the admin
whatever happens to the email, so nothing is lost while you sort it out.

**I have locked myself out.** Five wrong passwords locks your IP address for
fifteen minutes. Wait it out. If you have genuinely forgotten the password, it
needs resetting in the database — send me a message.

**A change has not appeared on the website.** Force a reload in your browser
(Ctrl+F5, or Cmd+Shift+R on a Mac). If it is still missing, it did not save —
check you pressed the Save button at the bottom of the panel.
