# Custom Map

LibreNMS has the ability to create custom maps to give a quick
overview of parts of the network including up/down status of devices
and link utilisation.  These are also referred to as weather maps.

## Viewer

When maps are created, they are visible to the users who
have read access to all devices on a given map.  Custom maps are available
through the Overview -> Maps -> Custom Maps menu.

Some key points about the viewer are:

 - Nodes change colour if they are down or disabled
 - Links are only associated with a single network interface
 - Link utilisation can only be shown if the link speed is known
 - Link speed is decoded from SNMP if possible (Upload/Download) and defaults
   to the physical speed if SNMP data is not available, or cannot be decoded
 - Links change colour as follows:
   - Black if the link is down, or the max speed is unknown
   - Green at 0% utilisation, with a gradual change to
   - Yellow at 50% utilisation, with a gradual change to
   - Orange at 75% utilisation, with a gradual change to
   - Red at 100% utilisation, with a gradual change to
   - Purple at 150% utilisation and above

### Viewer URL options

You can manually add the following parameters to a URL to alter the display of a
custom map.

The following URL options are available:

 - bare=yes : Removes the control bar from the top of the page.
 - screenshot=yes : Removes all labels from the nodes and links

e.g. If you want bare and screenshot enabled, https://_nmsserver_/maps/custom/2
becomes https://_nmsserver_/maps/custom/2?bare=yes&screenshot=yes

## Editor

To access the custom map editor, a user must be an admin.  The editor
is accessed through the Overview -> Maps -> Custom Map Editor menu.

In the editor, you get a drop-down list of all
the custom maps so you can choose one to edit, or select "Create New Map"
to create a new map.

### Map Settings

When you create a new map, you get a page to set
some global map settings.  These are:

 - *Name*: The name for the map
 - *Width*: The width of the map in pixels
 - *Height*: The height of the map in pixels
 - *Node Alignment*: When devices are added to the map, this aligns 
   the devices to an invisible grid this many pixels wide, which can help
   to make the maps look better.  This can be set to 0 to disable.
 - *Background*: An image (PNG/JPG) up to 2MB can be uploaded as a background.

These settings can be changed at any stage by clicking on the "Edit Map Settings"
button in the top-left of the editor.

### Nodes

Once you have a map, you can start by adding "nodes" to the map.  A node
represents a device, or an external point in the network (e.g. the internet)
To add a node, you click on the "Add Node" button in the control bar, then
click the map area where you want to add the node.  The system then asks you
for the following information:

 - *Label*: The text to display on this point in the network
 - *Device*: If this node represents a device, you can select the device from
   the drop-down.  This replaces the label, which you can then change if
   you want to.
 - *Style*: You can select the style of the node.  If a device has been selected
   you can choose the LibreNMS icon by choosing "Device Image".  You can also
   choose "Icon" to select an image for the device.
 - *Icon*: If you choose "Icon" in the style box, you can select from a list of
   images to represent this node

There are also options to choose the size and colour of the node and the font.

Once you have finished choosing the options for the node, you can press Save to
add it to the map.  NOTE: This does not save anything to the database immediately.
You need to click on the "Save Map" button in the top-right to save your changes
to the database.

You can edit a node at any time by selecting it on the map and clicking on the
"Edit Node" button in the control bar.

You can also modify the default settings for all new nodes by clicking on the
"Edit Node Default" button at the top of the page.

### Edges

Once you have 2 or more nodes, you can add links between the nodes.  These are
called edges in the editor.  To add a link, click on the "Add Edge" button in
the control bar, then click on one of the nodes you want to link and drag the
cursor to the second node that you want to attach.  The system then asks you for
the following information:

 - *From*: The node from which the link runs (the default is the first node that you selected)
 - *To*: The node to which the link runs (the default is the second node that you selected)
 - *Port*: If the From or To node is linked to a device, you can select an interface
   from one of the devices, and the custom map shows the traffic utilisation for
   the selected interface.
 - *Reverse Port Direction*: If the selected port displays data in the wrong
   direction for the link, you can reverse it by toggling this option.
 - *Line Style*: You can try different line styles, especially if you are running
   multiple links between the same 2 nodes
 - *Show percent usage*: Choose whether to have text on the lines showing the link
   utilisation as a percentage
 - *Recenter Line*: If you set this box, the centre point of the line moves
   back to half way between the 2 nodes when you click on the save button.

Once you have finished choosing the options for the node, you can press Save to
add it to the map.  NOTE: This does not save anything to the database immediately.
You need to click on the "Save Map" button in the top-right to save your changes
to the database.

When you push save, this creates 3 objects on the screen: 2 arrows and a
round node in the middle.  With the 3 objects, you can move the mid point
of the line off centre, and we can show bandwidth information for
both directions of the link.

You can edit an edge at any time by selecting it on the map and clicking on the
"Edit Edge" button in the control bar.

You can also modify the default settings for all new edges by clicking on the
"Edit Edge Default" button at the top of the page.

### Re-Render

When you pull items around the map, some of the lines bend. This causes a
"Re-Render Map" button to appear at the top-right of the page.  This button can be
clicked to draw all lines again, in the way in which the viewer shows them.

### Save Map

Once you are happy with a set of changes that you have made, you can click on the
"Save Map" button in the top-right of the page to commit changes to the database.
Then each person who sees the map gets the new version the next time their
page refreshes.

## Adding Images

You can add your own images to use on the custom map by copying files into the
html/images/custommap/icons/ directory.  Any files with a .svg, .png or .jpg extension
shows in the image selection drop-down in the custom map editor.

## Default configuration

The default configuration for all new maps can be set with the following commands:

```bash
lnms config:set custom_map.background_type
lnms config:set custom_map.background_data.color "#badaee"
lnms config:set custom_map.background_data.lat 40
lnms config:set custom_map.background_data.layer
lnms config:set custom_map.background_data.lng "-20"
lnms config:set custom_map.background_data.zoom 3
lnms config:set custom_map.edge_font_color "#343434"
lnms config:set custom_map.edge_font_face arial
lnms config:set custom_map.edge_font_size 12
lnms config:set custom_map.edge_seperation 10
lnms config:set custom_map.height "800px"
lnms config:set custom_map.legend_colours '{ "0": "#0000ff", "10": "#00ff00" }'
lnms config:set custom_map.node_align 10
lnms config:set custom_map.node_background "#D2E5FF"
lnms config:set custom_map.node_border "#2B7CE9"
lnms config:set custom_map.node_font_color "#343434"
lnms config:set custom_map.node_font_face arial
lnms config:set custom_map.node_font_size 14
lnms config:set custom_map.node_size 25
lnms config:set custom_map.node_type
lnms config:set custom_map.reverse_arrows false
lnms config:set custom_map.width "1800px"
```

All options can be reset to default by excluding the value argument.

Options with special requirements are as follows:
 - Specify all colours with the hex representation, not colour names
 - background_type, background_data.layer and node_type accept only valid values (see resources/definitions/config_definition.json)
 - custom_map.legend_colours is an array of lower percent and colour.  This means: in the example above, lines are green from 0-10%, then blue above 10%.
   - There is a special value of "-1" in the legend colours for when the interface is offline, or the port speed could not be determined
   - There is a special value of "-2" in the legend colours for when the device an interface is connected to is offline
   - The default legend_colours config is null. This means: lines change slowly from green->orange->red->purple as they go from 0-50-100-150%.
