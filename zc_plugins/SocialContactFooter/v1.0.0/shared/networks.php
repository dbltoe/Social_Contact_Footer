<?php
/**
 * Social Contact Footer -- shared network definitions.
 *
 * Required by BOTH the storefront renderer and the plugin's ScriptedInstaller,
 * so it deliberately has no Zen Cart dependencies at all: it just returns an
 * array.
 *
 * Keys per entry:
 *   label         Human-readable name, used to build the admin field title.
 *   entry_label   What the store owner actually types, e.g. "Page Name". The
 *                 admin field is titled "<label> <entry_label>:" so it is
 *                 obvious that only the distinguishing part is wanted.
 *   url_template  The full address, with %s standing in for what was typed.
 *                 Empty means the value is handled specially -- see
 *                 scf_network_href().
 *   example       Example fragment shown in the admin description.
 *   derived       Present and true when the plugin works the link out from the
 *                 store's own settings, so no configuration field is created.
 *   color         Brand color used when the icon style is "Brand colors".
 *   contrast      'light' = glyph drawn light on `color`; 'dark' = drawn dark.
 *   sort          Default display order (also the admin sort_order offset).
 *   svg           Inline 24x24 SVG glyph. See docs/CUSTOMIZING.md -- these are
 *                 simplified, hand-drawn marks, NOT the official brand logos.
 *
 * A store owner can always paste a complete https:// address instead of the
 * fragment; anything containing "/" or ":" is treated as a whole URL.
 *
 * @package  SocialContactFooter
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

return [
    'facebook' => [
        'label' => 'Facebook',
        'entry_label' => 'Page Name',
        'url_template' => 'https://www.facebook.com/%s',
        'example' => 'YourPageName',
        'color' => '#1877F2',
        'contrast' => 'light',
        'sort' => 10,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.51 1.49-3.9 3.78-3.9 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.45 2.89h-2.33v6.99A10 10 0 0 0 22 12z"/></svg>',
    ],
    'x' => [
        'label' => 'X (Twitter)',
        'entry_label' => 'Handle',
        'url_template' => 'https://x.com/%s',
        'example' => 'yourhandle',
        'color' => '#000000',
        'contrast' => 'light',
        'sort' => 20,
        'svg' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 3l16 18M20 3L4 21" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="square"/></svg>',
    ],
    'instagram' => [
        'label' => 'Instagram',
        'entry_label' => 'Handle',
        'url_template' => 'https://www.instagram.com/%s',
        'example' => 'yourhandle',
        'color' => '#E4405F',
        'contrast' => 'light',
        'sort' => 30,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><rect x="2.5" y="2.5" width="19" height="19" rx="5.5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.6" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.4"/></svg>',
    ],
    'youtube' => [
        'label' => 'YouTube',
        'entry_label' => 'Channel Handle',
        'url_template' => 'https://www.youtube.com/@%s',
        'example' => 'yourchannel',
        'color' => '#FF0000',
        'contrast' => 'light',
        'sort' => 40,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><rect x="1.5" y="4.8" width="21" height="14.4" rx="4.6" fill="none" stroke="currentColor" stroke-width="2"/><path d="M10.1 8.6l6.3 3.4-6.3 3.4z"/></svg>',
    ],
    'tiktok' => [
        'label' => 'TikTok',
        'entry_label' => 'Handle',
        'url_template' => 'https://www.tiktok.com/@%s',
        'example' => 'yourhandle',
        'color' => '#010101',
        'contrast' => 'light',
        'sort' => 50,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M16.2 2h-3.1v13.1a2.6 2.6 0 1 1-2.2-2.57V9.4a5.8 5.8 0 1 0 5.3 5.78V8.9a7 7 0 0 0 4 1.28V7.05a4.06 4.06 0 0 1-4-4.05z"/></svg>',
    ],
    'linkedin' => [
        'label' => 'LinkedIn',
        'entry_label' => 'Company Name',
        'url_template' => 'https://www.linkedin.com/company/%s',
        'example' => 'your-company',
        'color' => '#0A66C2',
        'contrast' => 'light',
        'sort' => 60,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><rect x="2.5" y="2.5" width="19" height="19" rx="3.5" fill="none" stroke="currentColor" stroke-width="2"/><rect x="6.2" y="10" width="2.6" height="8"/><circle cx="7.5" cy="7.3" r="1.6"/><path d="M11.2 18v-8h2.5v1.15c.52-.83 1.55-1.35 2.75-1.35 2.03 0 3.35 1.32 3.35 3.6V18h-2.6v-4.15c0-1.2-.55-1.9-1.6-1.9-1.02 0-1.8.72-1.8 1.95V18z"/></svg>',
    ],
    'pinterest' => [
        'label' => 'Pinterest',
        'entry_label' => 'Username',
        'url_template' => 'https://www.pinterest.com/%s',
        'example' => 'youraccount',
        'color' => '#BD081C',
        'contrast' => 'light',
        'sort' => 70,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M10.1 19.6c-.2-1-.05-2.3.15-3.2l1.05-4.5s-.27-.55-.27-1.35c0-1.27.73-2.2 1.65-2.2.78 0 1.15.58 1.15 1.28 0 .78-.5 1.95-.75 3.03-.22.9.45 1.64 1.34 1.64 1.6 0 2.85-1.7 2.85-4.14 0-2.17-1.55-3.68-3.78-3.68-2.57 0-4.08 1.93-4.08 3.92 0 .78.3 1.61.67 2.06.08.09.09.17.06.26l-.25 1c-.04.16-.13.2-.3.12-1.1-.51-1.8-2.13-1.8-3.43 0-2.79 2.03-5.36 5.85-5.36 3.07 0 5.46 2.19 5.46 5.12 0 3.05-1.93 5.51-4.6 5.51-.9 0-1.74-.47-2.03-1.02l-.55 2.1c-.2.77-.74 1.73-1.1 2.32z"/></svg>',
    ],
    'threads' => [
        'label' => 'Threads',
        'entry_label' => 'Handle',
        'url_template' => 'https://www.threads.net/@%s',
        'example' => 'yourhandle',
        'color' => '#000000',
        'contrast' => 'light',
        'sort' => 80,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 21.5c-5.4 0-8.5-3.3-8.5-9.5S6.6 2.5 12 2.5c4 0 6.7 1.9 7.6 5.1l-2.3.7C16.6 6 15 5 12 5c-3.9 0-5.9 2.3-5.9 7s2 7 5.9 7c2.8 0 4.5-1.3 4.5-3.2 0-1.5-.9-2.5-2.4-3-.3 2.3-1.7 3.7-3.8 3.7-1.9 0-3.2-1.2-3.2-2.9 0-1.9 1.6-3.1 4.2-3.1.6 0 1.2.05 1.7.14-.15-1.3-.9-2-2.1-2-.9 0-1.6.35-2.1 1.1l-1.9-1.2c.9-1.4 2.3-2.1 4-2.1 2.6 0 4.2 1.6 4.5 4.4 2 .9 3.1 2.5 3.1 4.6 0 3.3-2.7 5.5-6.5 5.5zm.4-8.6c-1.3 0-2 .4-2 1.2 0 .6.5 1 1.3 1 1.1 0 1.8-.8 2-2.1-.4-.07-.8-.1-1.3-.1z"/></svg>',
    ],
    'bluesky' => [
        'label' => 'Bluesky',
        'entry_label' => 'Handle',
        'url_template' => 'https://bsky.app/profile/%s',
        'example' => 'you.bsky.social',
        'color' => '#0285FF',
        'contrast' => 'light',
        'sort' => 90,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 10.8C10.9 8.6 7.9 4.5 5.1 3.5 3.6 3 2 3.4 2 5.3c0 1.9 1.1 6.4 1.7 7.2.6.8 1.9 1.1 3 1 .6-.06 1.2-.2 1.2-.2s-.5.15-1.1.4c-1.5.6-1.9 1.6-1.3 2.9.9 2 3.3 3.3 4.9 2.2 1-.7 1.7-2.3 2.6-4.4.9 2.1 1.6 3.7 2.6 4.4 1.6 1.1 4-.2 4.9-2.2.6-1.3.2-2.3-1.3-2.9-.6-.25-1.1-.4-1.1-.4s.6.14 1.2.2c1.1.1 2.4-.2 3-1 .6-.8 1.7-5.3 1.7-7.2 0-1.9-1.6-2.3-3.1-1.8-2.8 1-5.8 5.1-6.9 7.3z"/></svg>',
    ],
    'mastodon' => [
        'label' => 'Mastodon',
        'entry_label' => 'Handle',
        'url_template' => '',
        'example' => 'you@mastodon.social',
        'color' => '#6364FF',
        'contrast' => 'light',
        'sort' => 100,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M20.9 8.4c0-3.9-2.6-5-2.6-5C17 2.8 14.8 2.5 12.4 2.5h-.06c-2.4 0-4.6.3-5.9.9 0 0-2.6 1.1-2.6 5 0 .9-.02 2 .01 3.2.1 3.9.7 7.8 4.4 8.7 1.7.45 3.2.55 4.4.5 2.1-.1 3.3-.75 3.3-.75l-.07-1.5s-1.5.5-3.2.4c-1.7-.06-3.5-.2-3.8-2.3a4.3 4.3 0 0 1-.04-.6s1.7.4 3.8.5c1.3.06 2.5-.07 3.7-.2 2.4-.3 4.4-1.8 4.7-3.2.4-2.2.37-5.35.37-5.35zm-3 5h-1.9V8.8c0-1-.4-1.5-1.25-1.5-.9 0-1.4.6-1.4 1.8v2.4h-1.9V9.1c0-1.2-.5-1.8-1.4-1.8-.85 0-1.25.5-1.25 1.5v4.6H6.9V8.65c0-1 .25-1.8.77-2.4.53-.6 1.23-.9 2.1-.9 1 0 1.77.4 2.28 1.2l.47.8.48-.8c.5-.8 1.27-1.2 2.28-1.2.87 0 1.57.3 2.1.9.52.6.77 1.4.77 2.4z"/></svg>',
    ],
    'snapchat' => [
        'label' => 'Snapchat',
        'entry_label' => 'Username',
        'url_template' => 'https://www.snapchat.com/add/%s',
        'example' => 'yourhandle',
        'color' => '#FFFC00',
        'contrast' => 'dark',
        'sort' => 110,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2.5c2.9 0 4.6 2.1 4.6 4.9 0 .55-.04 1.35-.09 2.05.28.14.62.2.95.13.5-.1.95.2 1.05.62.1.42-.15.85-.6 1.05-.3.13-.9.34-1.35.5-.28.1-.4.35-.3.63.35 1 1.3 2.5 3.05 3.1.4.14.6.5.5.9-.1.4-.5.6-1.1.72-.5.1-1.1.18-1.3.4-.1.14-.13.4-.2.65-.08.3-.3.5-.65.45-.4-.05-1-.2-1.8-.06-.75.13-1.4.55-2.05 1.05-.5.4-1.1.7-1.81.7s-1.31-.3-1.81-.7c-.65-.5-1.3-.92-2.05-1.05-.8-.14-1.4.01-1.8.06-.35.05-.57-.15-.65-.45-.07-.25-.1-.51-.2-.65-.2-.22-.8-.3-1.3-.4-.6-.12-1-.32-1.1-.72-.1-.4.1-.76.5-.9 1.75-.6 2.7-2.1 3.05-3.1.1-.28-.02-.53-.3-.63-.45-.16-1.05-.37-1.35-.5-.45-.2-.7-.63-.6-1.05.1-.42.55-.72 1.05-.62.33.07.67.01.95-.13-.05-.7-.09-1.5-.09-2.05C7.4 4.6 9.1 2.5 12 2.5z"/></svg>',
    ],
    'reddit' => [
        'label' => 'Reddit',
        'entry_label' => 'Subreddit',
        'url_template' => 'https://www.reddit.com/r/%s',
        'example' => 'yoursubreddit',
        'color' => '#FF4500',
        'contrast' => 'light',
        'sort' => 120,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><circle cx="12" cy="13.4" r="8.6" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="9.1" cy="12.8" r="1.25"/><circle cx="14.9" cy="12.8" r="1.25"/><path d="M8.9 16.5c.85.7 1.9 1.05 3.1 1.05s2.25-.35 3.1-1.05" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="17.8" cy="3.9" r="1.8"/><path d="M12 4.6l4.2-.75" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>',
    ],
    'discord' => [
        'label' => 'Discord',
        'entry_label' => 'Invite Code',
        'url_template' => 'https://discord.gg/%s',
        'example' => 'aBcD1234',
        'color' => '#5865F2',
        'contrast' => 'light',
        'sort' => 130,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M19.3 5.6A16.3 16.3 0 0 0 15.4 4.4l-.2.4a15 15 0 0 0-6.4 0l-.2-.4a16.3 16.3 0 0 0-3.9 1.2C2.2 9.3 1.5 12.9 1.9 16.5a16.4 16.4 0 0 0 5 2.5l.9-1.4c-.55-.2-1.07-.45-1.55-.75l.38-.3a11.7 11.7 0 0 0 10.02 0l.38.3c-.48.3-1 .55-1.55.75l.9 1.4a16.4 16.4 0 0 0 5-2.5c.5-4.2-.75-7.8-3.08-10.9zM8.7 14.4c-.95 0-1.75-.9-1.75-2s.78-2 1.75-2 1.77.9 1.75 2c0 1.1-.78 2-1.75 2zm6.6 0c-.95 0-1.75-.9-1.75-2s.78-2 1.75-2 1.77.9 1.75 2c0 1.1-.78 2-1.75 2z"/></svg>',
    ],
    'twitch' => [
        'label' => 'Twitch',
        'entry_label' => 'Channel',
        'url_template' => 'https://www.twitch.tv/%s',
        'example' => 'yourchannel',
        'color' => '#9146FF',
        'contrast' => 'light',
        'sort' => 140,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M4.3 2L2.5 6.4v13.1h4.6V22h2.6l2.5-2.5h3.7l4.9-4.9V2zm14.3 11.8l-2.9 2.9h-4.6l-2.5 2.5v-2.5H5.8V3.9h12.8zM14.9 7.2h1.8v5.2h-1.8zm-4.8 0h1.8v5.2h-1.8z"/></svg>',
    ],
    'telegram' => [
        'label' => 'Telegram',
        'entry_label' => 'Channel',
        'url_template' => 'https://t.me/%s',
        'example' => 'yourchannel',
        'color' => '#26A5E4',
        'contrast' => 'light',
        'sort' => 150,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M21.7 3.3 2.6 10.7c-.9.35-.9 1.6.02 1.9l4.8 1.5 1.85 5.6c.25.75 1.2.95 1.72.35l2.5-2.85 4.8 3.5c.6.45 1.5.12 1.65-.62l3.2-15.1c.17-.8-.6-1.45-1.4-1.15zM8.9 13.6l8.9-5.5-7.3 6.6-.35 3.1z"/></svg>',
    ],
    'whatsapp' => [
        'label' => 'WhatsApp',
        'entry_label' => 'Number',
        'url_template' => 'https://wa.me/%s',
        'example' => '15551234567',
        'color' => '#25D366',
        'contrast' => 'light',
        'sort' => 160,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12.05 2.5A9.4 9.4 0 0 0 3.9 16.6L2.5 21.5l5.05-1.35A9.4 9.4 0 1 0 12.05 2.5zm0 1.9a7.5 7.5 0 1 1-3.8 13.95l-.33-.2-2.9.77.78-2.83-.22-.35A7.5 7.5 0 0 1 12.05 4.4zM8.7 7.5c-.18 0-.47.07-.72.34-.25.27-.95.93-.95 2.26s.98 2.62 1.11 2.8c.14.18 1.9 3.02 4.7 4.1 2.33.9 2.8.72 3.3.68.5-.05 1.62-.66 1.85-1.3.23-.64.23-1.19.16-1.3-.07-.11-.25-.18-.52-.32-.27-.14-1.62-.8-1.87-.89-.25-.09-.43-.14-.61.14-.18.27-.7.89-.86 1.07-.16.18-.32.2-.59.07-.27-.14-1.15-.42-2.19-1.35-.81-.72-1.36-1.61-1.52-1.88-.16-.27-.02-.42.12-.55.12-.12.27-.32.4-.48.14-.16.18-.27.27-.45.09-.18.05-.34-.02-.48-.07-.14-.61-1.48-.84-2.02-.22-.53-.44-.46-.61-.47z"/></svg>',
    ],
    'tumblr' => [
        'label' => 'Tumblr',
        'entry_label' => 'Blog Name',
        'url_template' => 'https://%s.tumblr.com',
        'example' => 'yourblog',
        'color' => '#36465D',
        'contrast' => 'light',
        'sort' => 170,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M14.6 21.5c-3.2 0-5.6-1.65-5.6-5.6v-5.6H6.4V7.6c2.9-.75 4.1-3.25 4.25-5.6h2.7v5.07h3.7v3.23h-3.7v4.9c0 1.57.8 2.1 2.05 2.1h1.8v4.2z"/></svg>',
    ],
    'vimeo' => [
        'label' => 'Vimeo',
        'entry_label' => 'Username',
        'url_template' => 'https://vimeo.com/%s',
        'example' => 'youraccount',
        'color' => '#1AB7EA',
        'contrast' => 'light',
        'sort' => 180,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M22.4 7.5c-.1 2.15-1.6 5.1-4.5 8.85-3 3.9-5.55 5.85-7.63 5.85-1.29 0-2.38-1.19-3.27-3.57l-1.79-6.55c-.66-2.38-1.37-3.57-2.13-3.57-.17 0-.75.35-1.75 1.04l-1.05-1.35c1.1-.97 2.19-1.94 3.26-2.91C5.02 4.02 6.12 3.3 6.87 3.23c1.77-.17 2.86 1.04 3.27 3.63.44 2.8.75 4.54.92 5.22.5 2.3 1.06 3.44 1.67 3.44.47 0 1.19-.75 2.14-2.25.95-1.5 1.46-2.64 1.53-3.42.14-1.32-.38-1.98-1.53-1.98-.55 0-1.11.13-1.69.38 1.12-3.67 3.26-5.45 6.42-5.35 2.34.07 3.44 1.59 3.3 4.56z"/></svg>',
    ],
    'rss' => [
        'label' => 'RSS',
        'entry_label' => 'Feed Path',
        'url_template' => '',
        'example' => 'blog/feed',
        'color' => '#FF6600',
        'contrast' => 'light',
        'sort' => 190,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><circle cx="6.2" cy="17.8" r="2.4"/><path d="M4 11.5a8.7 8.7 0 0 1 8.7 8.7" fill="none" stroke="currentColor" stroke-width="3"/><path d="M4 5.2a15 15 0 0 1 15 15" fill="none" stroke="currentColor" stroke-width="3"/></svg>',
    ],
    'email' => [
        'label' => 'Email',
        'entry_label' => '',
        'url_template' => '',
        'example' => '',
        'derived' => true,
        'color' => '#555555',
        'contrast' => 'light',
        'sort' => 200,
        'svg' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><rect x="2.5" y="4.5" width="19" height="15" rx="2.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3.5 6.5 12 13l8.5-6.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
    ],
];
