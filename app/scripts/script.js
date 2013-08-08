var dummyFisherman = {
    name: "soujisama", followerCount: "*", followingCount: "*", information: "lol wut?"
};

var dummyFollowQueue = [
    { selected: false, handle: "loading", followerCount: "follow", followingCount: "queue", tweetCount: "...", ldate: "please", adate: "wait" }
    //{ selected: true, handle: "two", followerCount: 23, followingCount: 45, tweetCount: 45564, ldate: "12 March 1880", adate: "14 June 2013" },
    //{ selected: false, handle: "three", followerCount: 23, followingCount: 45, tweetCount: 45564, ldate: "12 March 1880", adate: "14 June 2013" }
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
	
	$("#checkAllFollowers").on("click", function() {
		$(".followerCheckbox").attr("checked","checked");
	});
	
    var FishingBoat = function(fisherman, followqueue, searchresult) {
    	var self = this;
        self.fisherman = ko.observable(fisherman);
        self.followers = ko.observableArray([]);
        self.following = ko.observableArray([]);
        self.followQueue = ko.observableArray(followqueue);
        self.inquireQueue = ko.observableArray([]);
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
            	error: function(data) {
            		alert("do search says sum win wong <br/><br/>" + JSON.stringify(data));
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
			$.ajax({
				url: 'service.php',
				data: { type: 'getQueueItems', cursor: 0, queue: 0  },
				type: 'POST',
				success: function(data) {
					//alert(JSON.stringify(data));
					self.followQueue(data.queue);
				}
			});
        };
		
        this.getInquiryQueue = function() {
            this.doingSearch(false);
			$.ajax({
				url: 'service.php',
				data: { type: 'getQueueItems', cursor: 0, queue: 1  },
				type: 'POST',
				success: function(data) {
					//alert(JSON.stringify(data));
					self.followQueue(data.queue);
				}
			});
        };

        this.deleteFromQueue = function() {

        };
		
        self.addToQueue = function() {
			nowLoading();
			//alert(ko.toJS(self.tempList));
			$.ajax({
				url: 'service.php',
				data: { type: 'addToQueue', list: ko.toJS(self.tempList) },
				type: 'POST',
				success: function(data) {
					//alert('yes: ' + data);
					self.tempList.removeAll();
					self.getNext(0);
					self.getNext(1);
					doneLoading();
				},
				error: function(data) {
					alert('no: ' + JSON.stringify(data));
					doneLoading();
				}
			});
		};
		
		self.checkAllBoxes = function(isFollowingList, junk) {
			var checkAll = true;
			if(isFollowingList) {
				var list = ko.utils.arrayFilter(junk.followingList(),function(listItem) {
					return listItem().selected;
				});
				if(list.length == junk.followingList().length)
					checkAll = false;
				ko.utils.arrayForEach(junk.followingList(), function(item) {
					item().selected = checkAll;
					self.tempList.remove(item().handle_id);
					if(checkAll) self.tempList.push(item().handle_id);
				});

			}
			else {
				var list = ko.utils.arrayFilter(self.searchResult().followerList(),function(listItem) {
					return listItem().selected;
				});
				if(list.length == self.searchResult().followerList().length)
					checkAll = false;
				ko.utils.arrayForEach(self.searchResult().followerList(), function(item) {
					item().selected = checkAll;
					self.tempList.remove(item().handle_id);
					if(checkAll) self.tempList.push(item().handle_id);
				});
			}
			self.searchResult.valueHasMutated();
		};
		
		self.getNext = function(isFollowingList, junk) {
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
							//self.searchResult.followingList.removeAll();
							self.searchResult().followingList(data.list);
							self.makeAllObservables(self.searchResult().followingList);
							//ko.mapping.fromJSON(data.list,{},self.searchResult().followingList());
							self.searchResult().followingIndex = data.cursor;
							//self.makeAllObservables(self.searchResult);
							ko.utils.arrayForEach(self.searchResult().followingList(),function(listItem) {
								//self.searchResult().followingList().push(listItem);
								//console.dir(listItem);
								//alert(listItem().handle_id);
								if(self.tempList.indexOf(listItem().handle_id) >= 0) {
									//alert(listItem().selected);
									listItem().selected = true;
									//alert(listItem().selected);
								}
								///self.tempList.remove(listItem.handle_id);
								else if(listItem().selected) self.tempList.push(listItem().handle_id);
							});
						}
						else {
							self.searchResult().followerList(data.list);
							self.makeAllObservables(self.searchResult().followerList);
							self.searchResult().followerIndex = data.cursor;
							ko.utils.arrayForEach(self.searchResult().followerList(),function(listItem) {
								if(self.tempList.indexOf(listItem().handle_id) >= 0) {
									listItem().selected = true;
								}
								else if(listItem().selected) self.tempList.push(listItem().handle_id);
							});
						}
						/*else {
							self.searchResult().followerList(data.list);
							self.searchResult().followerIndex = data.cursor;
							ko.utils.arrayForEach(self.searchResult().followerList,function(listItem) {
								self.tempList.remove(listItem.handle_id);
								if(listItem.selected) self.tempList.push(listItem.handle_id);
							});
							self.makeAllObservables(self.searchResult().followerList);
						}						
						self.searchResult.valueHasMutated();*/
					}
					else {					
						alert(data.status.type + ": " + data.status.message);
					}
					
					self.searchResult.valueHasMutated();
					self.status(data.status);
					doneLoading();
            	},
            	error: function(data) {
            		alert("get next says sum win wong <br/><br/>"+JSON.stringify(data));
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
	
	$.ajax({
		url: 'service.php',
		data: { type: 'getQueueItems', cursor: 0, queue: 0  },
		type: 'POST',
		success: function(data) {
			//alert(JSON.stringify(data));
			//data = ko.toJSON(data);
			//alert(data);
			titanic.followQueue(data.queue);
			//titanic.status(data.status);
		}
	});
	
	$.ajax({
		url: 'service.php',
		data: { type: 'getQueueItems', cursor: 0, queue: 1  },
		type: 'POST',
		success: function(data) {
			//alert(JSON.stringify(data));
			//data = ko.toJSON(data);
			//alert(data);
			titanic.inquireQueue(data.queue);
			//titanic.status(data.status);
		}
	});
});