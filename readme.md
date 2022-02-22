<div align="center">

<h3 align="center">Bscscan API Wordpress Plugin</h3>

  <p align="center">
    Simple WP plugin to fetch token data from the bscscan API
  </p>
</div>

## Set-up

Activate the plugin and visit the BSC token stats admin page.

- Enter your bscscan API key
- Provide the token contract address
- Provide the token burn address
    - <small>Default: "0x000000000000000000000000000000000000dead"</small>

## Prerequisites

This plugin requires ACF PRO in order to work.

ACF is used to bootstrap the admin page and meta field data.

## How it works

The plugin will automatically update the following stats every hour.

- Total supply
- Total burned
- Circulating supply

To manually fetch token stats, disable & enable the plugin providing API key, contract & burn address have been provided.

### Customise the schedule

in bsc-api-fetch.php on line 44 you can modify the scheduled event.
See wordpress docs on [wp_schedule_event.](https://developer.wordpress.org/reference/functions/wp_schedule_event/) 

```
// Setup schedule
if (! wp_next_scheduled ( 'bsc_fetch_event' )) {
wp_schedule_event(time(), 'hourly', 'bsc_fetch_event');
}
```

<!-- CONTACT -->
## Contact

Jack Callow - [@jvckcallow](https://twitter.com/jvckcallow) - [Linkedin](https://www.linkedin.com/in/jack-callow-11002b8a/) - sjmc11@gmail.com

Project Link: [https://github.com/sjmc11/firebase-auth-go-kit](https://github.com/sjmc11/firebase-auth-go-kit)

<p align="right">(<a href="#top">back to top</a>)</p>
