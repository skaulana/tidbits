## kcom/tidbits

Just a smattering of miscellanea that don't really warrant their own repos.

* `chess_visualizer/` - shows the basic lines of attack and threats of a given
  chessboard state. Old school PHP with flat files.
* `distance_calculator/` - submission to an old interview question:
  "Given the latitude and longitude of an arbitrary location and a SQL table full
  of locations with their latitudes and longitudes, write some PHP code that will
  calculate and display the distance from the arbitrary location to every location
  within the table."
* `dwi/` - custom step files for [DWI](http://dwi.ddruk.com/), a DDR simulator.
* `k5/` - blogging scripts used to power [kaulana.com][1] in its 5th version. Useful
  as a complete PHP app, or as the platform layer powering a blog's backend.
* `meteor_cms/` - proof of concept for a voice & SMS content management system,
  written in JavaScript using the [Meteor](http://meteor.com) framework.
* `mfuof/` - e.g. Move Files Up One Folder. Applet intended to be placed in the
  Windows '[Send To][2]' folder as an accelerator.
* `ray_tracer/` - basic ray tracer built for my undergrad computer graphics class.
* `stylish_css/` - stylesheets submitted to [userstyles.org][3] for use with the
  Stylish browser extension.
* `win_drawlines/` - submission to an old C++ GUI programming class assignment.
  Draws lines on screen by handling low-level Win32 messages.
* `algo4.cs` - solution to the strongly connected components (SCC) problem from
  the [Coursera](http://coursera.org) MOOC [Algorithms I][4].
* `dateheaderline.c` - simple [Pidgin](http://pidgin.im/) plugin to add a date line
  to new IM windows. Originally built for version 2.6.4; build the DLL and drop in
  your _%APPDATA%\.purple\plugins_ directory for use.
* `hexparse.php` - generates Verilog code to initialize a BlockRAM with hex data.
* `pingnlog.sh` - simple shell script to periodically check a URL for uptime and
  output the results to a flat file. Useful with crontab.
* `tfs_feature_cards.html` - creates printable cards for [TFS][5] features using
  the CSV export of a features query.

## License

BSD for my code as per [license.md][6], except where superseded by the presence of
a separate license document in the subfolder. Dependencies are licensed as noted
in their subfolders as well.

[1]: http://kaulana.com/
[2]: https://support.microsoft.com/en-us/kb/310270
[3]: https://userstyles.org/users/301651
[4]: https://class.coursera.org/algo/class/index
[5]: https://www.visualstudio.com/en-us/products/tfs-overview-vs.aspx
[6]: /license.md
