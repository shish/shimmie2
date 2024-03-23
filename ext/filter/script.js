// This code is a modified, jquery-less adaptation of danbooru/app/javascript/src/javascripts/blacklists.js, with snippets from utility.js
// The following copyright notice is a legal requirement of Danbooru's BSD 2-Clause license

// Copyright (c) 2013~2021, Danbooru Project
// All rights reserved.

let Utility = {};

Utility.is_subset = function(array, subarray) {
  var all = true;

  $.each(subarray, function(i, val) {
    if ($.inArray(val, array) === -1) {
      all = false;
    }
  });

  return all;
}

Utility.intersect = function(a, b) {
  a = a.slice(0).sort();
  b = b.slice(0).sort();
  var result = [];
  while (a.length > 0 && b.length > 0) {
    if (a[0] < b[0]) {
      a.shift();
    } else if (a[0] > b[0]) {
      b.shift();
    } else {
      result.push(a.shift());
      b.shift();
    }
  }
  return result;
}

let Filter = {};

Filter.entries = [];

Filter.parse_entry = function(string) {
  var entry = {
    "tags": string,
    "require": [],
    "exclude": [],
    "optional": [],
    "disabled": false,
    "hits": 0,
    "min_score": null
  };

  let tags = string.split(/ +/);
  tags.forEach(function(tag) {
    if (tag.charAt(0) === '-') {
      entry.exclude.push(tag.slice(1));
    } else if (tag.charAt(0) === '~') {
      entry.optional.push(tag.slice(1));
    } else if (tag.match(/^score:<.+/)) {
      var score = tag.match(/^score:<(.+)/)[1];
      entry.min_score = parseInt(score);
    } else {
      entry.require.push(tag);
    }
  });
  return entry;
}

Filter.parse_entries = function() {
  var entries = document.getElementById("filter-tags").getAttribute("tags").replace(/(rating:\w)\w+/ig, "$1").toLowerCase().split(/[,\n]/);
  entries = entries.filter(e => e.trim() !== "");

  entries.forEach(function(tags) {
    var entry = Filter.parse_entry(tags);
    Filter.entries.push(entry);
  });
}

Filter.toggle_entry = function(e) {
  var link = e.target;
  var tags = link.innerText;
  var match = Filter.entries.find(function(entry, i) {
    return entry.tags === tags;
  });
  if (match) {
    match.disabled = !match.disabled;
    if (match.disabled) {
      link.classList.add("filter-inactive");
    } else {
      link.classList.remove("filter-inactive");
    }
  }
  Filter.apply();
  e.preventDefault();
}

Filter.update_sidebar = function() {
  Filter.entries.forEach(function(entry) {
    if (entry.hits.length === 0) {
      return;
    }

    var item = document.createElement("li");
    var link = document.createElement("a");
    var count = document.createElement("span");

    link.innerText = entry.tags;
    link.classList.add("filter");
    link.setAttribute("href", `/posts?tags=${encodeURIComponent(entry.tags)}`);
    link.setAttribute("title", entry.tags);
    link.addEventListener("click", Filter.toggle_entry);
    let unique_hits = new Set(entry.hits).size;
    count.innerText = unique_hits;
    count.classList.add("tag_count");
    item.append(link);
    item.append(" ");
    item.append(count);

    document.getElementById("filter-list").append(item);
  });

  document.getElementById("Filtersleft").style.display = "";
}

Filter.disable_all = function() {
  Filter.entries.forEach(function(entry) {
    entry.disabled = true;
  });
  // There is no need to process the filter when disabling
  Array.from(Filter.posts()).forEach(function(post) {
    post.classList.remove("filtered-active");
  });
  document.getElementById("disable-all-filters").style.display = "none";
  document.getElementById("re-enable-all-filters").style.display = "";
  Array.from(document.getElementsByClassName("filter")).forEach(function(post) {
    post.classList.add("filter-inactive");
  });
}

Filter.enable_all = function() {
  Filter.entries.forEach(function(entry) {
    entry.disabled = false;
  });
  Filter.apply();
  document.getElementById("disable-all-filters").style.display = "";
  document.getElementById("re-enable-all-filters").style.display = "none";
  Array.from(document.getElementsByClassName("filter")).forEach(function(post) {
    post.classList.remove("filter-inactive");
  });
}

Filter.initialize_disable_all_filters = function() {
  if (shm_cookie_get("ui-disable-filters") === "1") {
    Filter.disable_all();
  } else {
    // The filter has already been processed by this point
    document.getElementById("disable-all-filters").style.display = "";
  }

  document.getElementById("disable-all-filters").addEventListener("click", function(e) {
    shm_cookie_set("ui-disable-filters", "1");
    Filter.disable_all();
    e.preventDefault();
  });

  document.getElementById("re-enable-all-filters").addEventListener("click", function(e) {
    shm_cookie_set("ui-disable-filters", "0");
    Filter.enable_all();
    e.preventDefault();
  });
}


Filter.apply = function() {
  Filter.entries.forEach(function(entry) {
    entry.hits = [];
  });

  var count = 0
  Array.from(Filter.posts()).forEach(function(post) {
    count += Filter.apply_post(post);
  });

  return count;
}

Filter.apply_post = function(post) {
  var post_count = 0;
  Filter.entries.forEach(function(entry) {
    if (Filter.post_match(post, entry)) {
      let post_id = post.getAttribute("data-post-id");
      entry.hits.push(post_id);
      post_count += 1;
    }
  });
  if (post_count > 0) {
    Filter.post_hide(post);
  } else {
    Filter.post_unhide(post);
  }
  return post_count;
};

Filter.posts = function() {
  return document.getElementsByClassName("thumb");
}

Filter.post_match = function(post, entry) {
  if (entry.disabled) {
    return false;
  }

  var score = parseInt(post.getAttribute("data-score"));
  var score_test = entry.min_score === null || score < entry.min_score;

  var tags = post.getAttribute("data-tags").split(/ +/);
//  tags.push(...(post.getAttribute("data-pools")).split(/ +/));
  tags.push("rating:" + post.getAttribute("data-rating"));
//  tags.push("uploaderid:" + post.getAttribute("data-uploader-id"));
//  post.getAttribute("flags").split(/ +/).forEach(function(v) {
//    tags.push("status:" + v);
//  });

  return (Utility.is_subset(tags, entry.require) && score_test)
    && (!entry.optional.length || Utility.intersect(tags, entry.optional).length)
    && !Utility.intersect(tags, entry.exclude).length;
}

Filter.post_hide = function(post) {
  post.classList.add("filtered");
  post.classList.add("filtered-active");  

  var video = post.querySelector("video");
  if (video) {
    video.pause();
    video.currentTime = 0;
  }
}

Filter.post_unhide = function(post) {
  post.classList.add("filtered")
  post.classList.remove("filtered-active");

  var video = post.querySelector("video");
  if (video) {
    video.play();
  }
}

Filter.initialize_all = function() {
  Filter.parse_entries();

  if (Filter.apply() > 0) {
    Filter.update_sidebar();
    Filter.initialize_disable_all_filters();
  } else {
    document.getElementById("Filtersleft").style.display = "none";
  }
}

if (document.getElementById("Filtersleft")) {
  Filter.initialize_all();
}
