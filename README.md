# upcoming-geocaching-events
A Wordpress plugin for creating an automatically updating list of geocaching events

### Settings
#### Event Feed URL
The URL to fetch the list of events from. Expects JSON data to be returned in the format below.
#### State
The state to show local events for.
#### Your Owner ID
A groundspeak user ID number. Any events hosted by that user will be highlighted.
#### Your Logo URL
URL to a logo that can be used in place of the user's name. Used for events hosted by the user ID in the `Your Owner ID` setting.

### Event data format
This plugin expects JSON data in the following format
```
{
    "events": [
        {
            "country": "",
            "event_date": "",
            "event_name": "",
            "event_status": "E",
            "event_type": "Event",
            "gc_code": "GC",
            "osm_state": "",
            "osm_town": "",
            "owner_id": 0,
            "owner_name": "",
            "placed_by": "",
            "state": ""
        }
    ],
    "megas": [
        ...
    ],
    "others": [
        {
            "other_name": "",
            ...
        }
    ]
}
```
