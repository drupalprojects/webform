/**
 * @file
 * Jquery plugin to find common ancestor.
 *
 * @see http://stackoverflow.com/questions/3217147/jquery-first-parent-containing-all-children
 */

(function ($) {

  'use strict';

  jQuery.fn.commonAncestor = function() {
    var parents = [];
    var minlen = Infinity;

    $(this).each(function() {
      var curparents = $(this).parents();
      parents.push(curparents);
      minlen = Math.min(minlen, curparents.length);
    });

    for (var i in parents) {
      parents[i] = parents[i].slice(parents[i].length - minlen);
    }

    // Iterate until equality is found
    for (var i = 0; i < parents[0].length; i++) {
      var equal = true;
      for (var j in parents) {
        if (parents[j][i] != parents[0][i]) {
          equal = false;
          break;
        }
      }
      if (equal) return $(parents[0][i]);
    }
    return $([]);
  }

})(jQuery);

