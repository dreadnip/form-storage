# Formspree for dummies

Single-file proof-of-concept for a self-hosted Formspree alternative. Can be used to accept all incoming POST calls and store them in an sqlite database file. Useful for forms on static sites.

NOT safe, do NOT use this as-is. For this to be really useful you'd have to make the form keys unique, and enforce usage of each key by only 1 user/domain. Right now everyone can write to any key with any content/fields.

Example:
```html
<form action="https://yourserver.com/someRandomKey" method="post">
    <label for="email">Your Email</label>
    <input name="email" id="email" type="email">
    <label for="message">Message</label>
    <textarea name="message" id="message"></textarea>
    <input type="hidden" name="_redirect" value="https://yourstaticsite.com/thanks.html" />
    <!-- a very basic honeypot for spam prevention -->
    <input type="text" name="_dry" style="display:none !important" tabindex="-1" autocomplete="false" />
    <button type="submit">Submit</button>
</form>
```

Submitting this form will create a new entry in the sqlite database on your server for key "someRandomKey" and the entire POST body (email and message) as a JSON encoded string. You can then query this data and handle it as you please (mail it to someone, export it as a CSV, send it to Google Spreadsheets, etc..).