var dummyFisherman = {
    name: "soujisama", followerCount: "*", followingCount: "*"
};

var dummyFollowQueue = [
    { selected: false, handle: "one", followers: 23, following: 45, tweetCount: 45564, ldate: "12 March 1880", adate: "14 June 2013" },
    { selected: true, handle: "two", followers: 23, following: 45, tweetCount: 45564, ldate: "12 March 1880", adate: "14 June 2013" },
    { selected: false, handle: "three", followers: 23, following: 45, tweetCount: 45564, ldate: "12 March 1880", adate: "14 June 2013" }
];

var dummySearchResult = {
            handle: ko.observable(),
            followerCount: ko.observable(),
            followingCount: ko.observable(),
            followerList: ko.observableArray([]),
            followingList: ko.observableArray([]),
            status: {
                message: "",
                type: ""
            }
        };
function flashStatus() {
    var $bar = $('#statusBar');
    /*if ($bar.attr('data-type') == '200') $bar.addClass("alert-success");
    else if ($bar.attr('data-type') == 'warn') $bar.addClass("alert-warning");
    else if ($bar.attr('data-type') == 'info') $bar.addClass("alert-info");
    else if ($bar.attr('data-type') == 'error') $bar.addClass("alert-error");*/
    $bar.slideDown().delay(2000).slideUp('slow');
}

function nowLoading() {
	$("#loader").addClass("showLoader");
}

function doneLoading() {
	$("#loader").removeClass("showLoader");
}


//on load
$(function () {
    flashStatus();

    var FishingBoat = function(fisherman, followqueue, searchresult) {
    	var self = this;
        self.fisherman = ko.observable(fisherman);
        self.followers = ko.observableArray([]);
        self.following = ko.observableArray([]);
        //this.followQueue = ko.observableArray([]);
        self.followQueue = ko.observableArray(followqueue);
        self.inquiryQueue = ko.observableArray([]);
        self.searchResult = ko.observable(searchresult);
        self.status = ko.observable();
        self.searchString = ko.observable();
        self.doingSearch = ko.observable(false);
        self.notDoingSearch = ko.computed(function() {
            return !self.doingSearch;
        });     
        self.tempList = ko.observableArray([]);

        self.doSearch = function() {
            self.doingSearch(true);
            nowLoading();
            $.ajax({
            	url: 'service.php',
            	data: { type: 'getUserInfo', handle: this.searchString },
            	type: 'POST',
            	success: function(data) {
					if(data.status.type != "error") {
						//ko.mapping.fromJSON(data,{},self.searchResult());
						//alert(self.searchResult);
						//alert(newdata);
						//alert(data);						
						/*self.searchResult().handle(data.handle);
						self.searchResult().followerCount(data.followerCount);
						self.searchResult().followingCount(data.followingCount);
						self.searchResult().followerList(data.followerList);
						self.searchResult().followingList(data.followingList);
						self.searchResult.valueHasMutated();*/
						self.searchResult(data);
						//ko.mapping.fromJS(data,{},self.searchResult());
						ko.utils.arrayForEach(self.searchResult().followingList,function(listItem) {
							self.tempList.remove(listItem.handle_id);
							if(listItem.selected) self.tempList.push(listItem.handle_id);
						});
						ko.utils.arrayForEach(self.searchResult().followerList,function(listItem) {
							self.tempList.remove(listItem.handle_id);
							if(listItem.selected) self.tempList.push(listItem.handle_id);
						});
						//data = ko.utils.parseJSON(data);
						//ko.mapping.fromJS(data,{},self.searchResult);//()
						self.makeAllObservables(self.searchResult);
						//self.searchResult().followerList = ko.observable(self.searchResult().followerList);
						//self.searchResult().followerList().selected = ko.observable(self.searchResult().followerList().selected);
						//alert(ko.isObservable(self.searchResult().followerList));
					}
					else {					
						alert(data.status.type + ": " + data.status.message);
					}
					self.status(data.status);
					doneLoading();
            	},
            	error: function() {
            		alert("sum win wong");
            		doneLoading();
            	}
            });
            /*this.searchResult({
                name: "john nutella",
                followerCount: 12,
                followingCount: 14,
                followerList: [
                    { name: "werlol", followerCount: 2, followingCount: 5, tweetCount: 9876, ldate: "23 May 1950", adate: "9 Dec 2015", selected: false },
                    { name: "werlol", followerCount: 3, followingCount: 5, tweetCount: 9876, ldate: "23 May 1950", adate: "9 Dec 2015", selected: false },
                ],
                followingList: [
                    { name: "inglol", followerCount: 9, followingCount: 5, tweetCount: 9876, ldate: "23 May 1950", adate: "9 Dec 2015", selected: false },
                    { name: "inglol", followerCount: 8, followingCount: 5, tweetCount: 9876, ldate: "23 May 1950", adate: "9 Dec 2015", selected: false },
                ],
                status: {
                    type: "success",
                    message: "good one"
                }
            })*/
        };
        this.showSettings = function() {
            alert("settings");
        };
        this.getFollowers = function() {

        };
        this.getFollowing = function() {

        };
        this.getFollowQueue = function() {
            this.doingSearch(false);
        };
        this.getInquiryQueue = function() {

        };
        this.deleteFromQueue = function() {

        };
        this.addToQueue = function() {

        };
		
		self.getNext = function(junk, isFollowingList) {
			var cursor = 0;
			if(isFollowingList) cursor = self.searchResult().followingIndex;
			else index = self.searchResult().followerIndex;
			$.ajax({
            	url: 'service.php',
            	data: { type: 'getNextList', cursor: cursor, list: isFollowingList },
            	type: 'POST',
            	success: function(data) {
					self.status(data.status);
					if(data.status.type != "error") {
						if(isFollowingList) {
							self.searchResult().following(data.list);
							self.searchResult().followingIndex(data.cursor);
							ko.utils.arrayForEach(self.searchResult().following,function(listItem) {
								self.tempList.remove(listItem.handle_id);
								if(listItem.selected) self.tempList.push(listItem.handle_id);
							});
						}
						else {
							self.searchResult.followers(data.list);
							self.searchResult.followerIndex(data.cursor);
							ko.utils.arrayForEach(self.searchResult().followers,function(listItem) {
								self.tempList.remove(listItem.handle_id);
								if(listItem.selected) self.tempList.push(listItem.handle_id);
							});
						}
					}
					else {					
						alert(data.status.type + ": " + data.status.message);
					}
					self.status(data.status);
					doneLoading();
            	},
            	error: function() {
            		alert("sum win wong");
            		doneLoading();
            	}
            });
		};
		
		self.listChecked = function(listItem) {
			self.tempList.remove(listItem.handle_id);
			if(listItem.selected) self.tempList.push(listItem.handle_id);
			//else self.tempList.remove(listItem);
			//setTimeout(function() {
				//alert(ko.isObservable(self.searchResult().followerList()));
				//listItem = ko.observable(listItem);
				//self.searchResult().followerList().push(listItem);
				//self.searchResult().followerList.valueHasMutated();
				//self.searchResult().followingList.valueHasMutated();
				//self.searchResult.valueHasMutated();
				//self.searchResult(self.searchResult());
			//},0);
			return true;
			//self.followerList().valueHasMutated();
		};
		
		self.selectedFollowerCount = ko.computed(function() {
			var count = 0;
			/*if(self.searchResult()) {
				if(self.searchResult().followerList) {
					for(var i = 0; i < self.searchResult().followerList.length; i++) {
						//alert(self.searchResult().followingList[i].selected);
						if(self.searchResult().followerList[i].selected) count++;
					}
				}
			}*/
			/*ko.utils.arrayForEach(self.searchResult().followerList, function(item) {
				//alert(item.handle);
				if(item.selected) count ++;
			});*/
			//alert(count);
			var list = ko.utils.arrayFilter(self.searchResult().followerList,function(listItem) {
				//alert(listItem.selected);
				return listItem.selected;
			});
			//alert(list.length);
			return list.length;
		});
		
		self.selectedFollowingCount = ko.computed(function() {
			var list = ko.utils.arrayFilter(self.searchResult().followingList,function(listItem) {
				//alert(listItem.selected);
				return listItem.selected;
			});
			//alert(list.length);
			return list.length;
		});
		
		self.makeAllObservables = function (observable) {
			// Loop through its children
			for (var child in observable()) {
				// If this child is not an observable and is an object
				if ((!ko.isObservable(observable()[child])) && (typeof observable()[child] === "object")) {
					// Make it an observable
					observable()[child] = ko.observable(observable()[child]);
					// Make all of its children observables
					self.makeAllObservables(observable()[child]);
				}
				//alert('new observable' + ko.toJSON(child));
			}
    	};

		
        this.init = function() {
            //populate fisherman
            //populate followqueue
        };
    }

    ko.bindingHandlers.slideVisible = {
        init: function(element, valueAccessor) {
            var value = valueAccessor();
            $(element).toggle(ko.utils.unwrapObservable(value));
        },
        update: function(element, valueAccessor) {
            var value = valueAccessor();
            ko.utils.unwrapObservable(value) ? $(element).slideDown() : $(element).slideUp();
        }
    };

    var titanic = new FishingBoat(dummyFisherman, dummyFollowQueue, dummySearchResult);
    ko.applyBindings(titanic);

    $.ajax({
        url: 'service.php',
        data: { type: 'getOwnerInfo' },
        type: 'POST',
        success: function(data) {
            //alert(data);
            //data = ko.toJSON(data);
            //alert(data);
            titanic.fisherman(data);
            titanic.status(data.status);
        }
    });
});