This folder holds an optional header image for the newsletter emails this plugin
sends -- the confirmation request, the welcome message, and the registration
invitation.

IT IS OPTIONAL, AND THERE IS NO IMAGE UNLESS YOU ADD ONE
--------------------------------------------------------
With no file here, those three emails go out with no image at the top at all --
not your store logo, nothing. That is deliberate, and it is the default.

The easiest way to add one is the "Newsletter Email Header Image" field on
Admin > Tools > Footer Newsletter Subscribers. That handles everything below
automatically, including replacing whatever was here before.

To place a file here directly instead (e.g. by FTP), name it exactly:

  header.jpg   (or header.jpeg, header.png, header.gif, header.webp)

Only the first matching file found is used, checked in that order.

SIZE AND FORMAT
---------------
Most email headers are 550 x 110 pixels, and that is the size to aim for.
Nothing is resized, cropped or rejected on those numbers -- whatever you supply
is sent at its own natural size -- but much wider than that gets scaled down or
cropped by mail clients on a phone.

Accepted formats: jpg, jpeg, png, gif, webp. Nothing else, because Zen Cart's
shipped zc_plugins/.htaccess denies every other file type in this folder, so an
image in another format would upload happily and then fail to load in every
subscriber's mail client.

WHAT IT AFFECTS
---------------
These three emails and nothing else. Your store's own header image is not
touched, moved or overwritten, and order confirmations, account notices,
contact-us replies and everything else keep the store logo exactly as they are
now.

The image must be reachable at a normal public URL, which is how any email
header image works. This folder is readable for exactly that reason.

It is only ever used in the HTML version of an email. Subscribers who chose
TEXT-Only see no difference, and no image is ever attached to a message.

ABOUT spacer.png
----------------
Do not delete it. Zen Cart's email template has the header image tag built into
it, and a plugin cannot remove that tag on any release before v3.0 -- leaving
the address empty makes Zen Cart fill in the store logo, which is exactly what
we do not want. Pointing the tag at that 1x1 fully transparent image is how
these emails end up with no visible header. It is not a tracking pixel: it is
the same file for every recipient and records nothing.
