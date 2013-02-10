
            ZeroClipboard.setMoviePath("scripts/ZeroClipboard.swf");
        var clip = new ZeroClipboard.Client("#copy-button");
        clip.on( 'load', function(client) {
          alert( "movie is loaded" );
        } );

        clip.on( 'complete', function(client, args) {
          alert("Copied text to clipboard: " + args.text );
        } );

        clip.on( 'mouseover', function(client) {
          // alert("mouse over");
        } );

        clip.on( 'mouseout', function(client) {
          // alert("mouse out");
        } );

        clip.on( 'mousedown', function(client) {

          // alert("mouse down");
        } );

        clip.on( 'mouseup', function(client) {
          // alert("mouse up");
        } );