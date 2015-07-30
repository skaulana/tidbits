// DB (insecure, autopublished) metadata and files for voice/auto-incrementing IDs
ContentStore = new Mongo.Collection("content");
FileStore = new FS.Collection("files", {
    stores: [new FS.Store.FileSystem("files")]
});
CounterStore = new Mongo.Collection("counters");

// Client code
if (Meteor.isClient) {
  Session.setDefault('filterBy', '');

  Template.shell.helpers({
      totalItems: function () {
          return ContentStore.find({}).count();
      },
      pluralize: function (num) {
          return num != 1; // hack to add 's' in template total
      }
  });
  Template.hello.helpers({
    content: function () {
        if (! Session.get('filterBy')) return ContentStore.find({});
        return ContentStore.find({
            $where: "this.text.toLowerCase().indexOf('" + Session.get('filterBy') + "') >= 0"
        });
    },
    displaydate: function(d) {
        return moment(d).format("YYYY-MMM-DD h:mma");
        // todo: store in UTC and respect user's timezone
    },
    showIfVoice: function (t) {
        return t == "voice" ? "" : "hidden"; // todo: don't inject CSS directly
    },
    getFileUrl: function (fileId) {
        if (!fileId) return "";        
        var fileObj = FileStore.findOne(fileId);
        return fileObj.url({download: true});
    }
  });

  Template.hello.events({
    'submit .commandbar': function (event) {
        Session.set('filterBy', event.target.filter.value);
        return false;
    },
    'click #addsample': function (event) {
        ContentStore.insert({
            contentId: getNextSequence("contentId"),
            format: "SMS",
            fileId: '',
            language: "en-us",
            text: "this is an example of an SMS message " + (Math.random() > 0.5 ? "one" : "two"),
            topics: [ "health", "nutrition" ],
            audience: [ "youth" ],
            type: "reminder",
            createdAt: new Date()
        });
        return false;
    }
  });
  
  Template.upload.helpers({
      files: function() {
          return FileStore.find({});
      }
  });
  
  Template.upload.events({
  // Catch the dropped event
      'dropped #dropzone': function(event, template) {
          console.log('files dropped');
          FS.Utility.eachFile(event, function(file) {
              FileStore.insert(file, function (err, fileObj) {
                  //If !err, we have inserted new doc with ID fileObj._id, and
                  //kicked off the data upload using HTTP
                  console.log(fileObj);
                  ContentStore.insert({
                      contentId: getNextSequence("contentId"),
                      format: "voice",
                      fileId: fileObj._id,
                      language: "en-us",
                      text: "this is a sample transcript " + (Math.random() > 0.5 ? "one" : "two"),
                      topics: [ "family", "nutrition" ],
                      audience: [ "youth" ],
                      type: "story message",
                      createdAt: new Date()
                  });
              });
          });
      }
  });
  
  Template.confirmnuke.events({
      'click #nuke': function(event, template) {
          Meteor.call("dropAllData");
          Router.go('/');
          return false; // prevents default click action
      }
  });
}

// Routing
Router.configure({
    layoutTemplate: 'shell'
});
Router.route('/', function() {
    this.render('hello', {to: 'base'});
    this.render('', {to: 'extra'});
});
Router.route('/there', function() {
    this.render('there', {to: 'base'});
});
Router.route('/upload', function() {
    this.render('upload', {to: 'base'});
});
Router.route('/confirmnuke', function() {
    this.render('confirmnuke', {to: 'extra'});
});

// Secure methods
Meteor.methods({
    dropAllData: function() {
        if (! Meteor.userId()) {
            throw new Meteor.error("unauthorized!");
        }
        FileStore.remove({});
        ContentStore.remove({});
        CounterStore.remove({});
        CounterStore.insert({ name: "contentId", seq: 0});        
    }
});

// Server code
if (Meteor.isServer) {
    Meteor.startup(function () {
        // code to run on server at startup
        if (!CounterStore.find({name: "contentId"}).count()) {
            CounterStore.insert({
                name: "contentId",
                seq: 0
            });
        }
    });
}

// Miscellaneous
function getNextSequence(name) {
    var ret = CounterStore.findOne({name: "contentId"});
    CounterStore.update({_id: ret._id}, {$inc: { seq: 1 }});
    return ret.seq + 1;
}