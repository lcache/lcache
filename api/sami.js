
(function(root) {

    var bhIndex = null;
    var rootPath = '';
    var treeHtml = '        <ul>                <li data-name="namespace:LCache" class="opened">                    <div style="padding-left:0px" class="hd">                        <span class="glyphicon glyphicon-play"></span><a href="LCache.html">LCache</a>                    </div>                    <div class="bd">                                <ul>                <li data-name="class:LCache_APCuL1" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="LCache/APCuL1.html">APCuL1</a>                    </div>                </li>                            <li data-name="class:LCache_Address" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="LCache/Address.html">Address</a>                    </div>                </li>                            <li data-name="class:LCache_DatabaseL2" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="LCache/DatabaseL2.html">DatabaseL2</a>                    </div>                </li>                            <li data-name="class:LCache_Entry" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="LCache/Entry.html">Entry</a>                    </div>                </li>                            <li data-name="class:LCache_Integrated" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="LCache/Integrated.html">Integrated</a>                    </div>                </li>                            <li data-name="class:LCache_L1" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="LCache/L1.html">L1</a>                    </div>                </li>                            <li data-name="class:LCache_L2" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="LCache/L2.html">L2</a>                    </div>                </li>                            <li data-name="class:LCache_LX" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="LCache/LX.html">LX</a>                    </div>                </li>                            <li data-name="class:LCache_NullL1" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="LCache/NullL1.html">NullL1</a>                    </div>                </li>                            <li data-name="class:LCache_StaticL1" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="LCache/StaticL1.html">StaticL1</a>                    </div>                </li>                            <li data-name="class:LCache_StaticL2" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="LCache/StaticL2.html">StaticL2</a>                    </div>                </li>                            <li data-name="class:LCache_UnserializationException" class="opened">                    <div style="padding-left:26px" class="hd leaf">                        <a href="LCache/UnserializationException.html">UnserializationException</a>                    </div>                </li>                </ul></div>                </li>                </ul>';

    var searchTypeClasses = {
        'Namespace': 'label-default',
        'Class': 'label-info',
        'Interface': 'label-primary',
        'Trait': 'label-success',
        'Method': 'label-danger',
        '_': 'label-warning'
    };

    var searchIndex = [
                    
            {"type": "Namespace", "link": "LCache.html", "name": "LCache", "doc": "Namespace LCache"},
            
            {"type": "Class", "fromName": "LCache", "fromLink": "LCache.html", "link": "LCache/APCuL1.html", "name": "LCache\\APCuL1", "doc": "&quot;&quot;"},
                                                        {"type": "Method", "fromName": "LCache\\APCuL1", "fromLink": "LCache/APCuL1.html", "link": "LCache/APCuL1.html#method___construct", "name": "LCache\\APCuL1::__construct", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\APCuL1", "fromLink": "LCache/APCuL1.html", "link": "LCache/APCuL1.html#method_getKeyOverhead", "name": "LCache\\APCuL1::getKeyOverhead", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\APCuL1", "fromLink": "LCache/APCuL1.html", "link": "LCache/APCuL1.html#method_setWithExpiration", "name": "LCache\\APCuL1::setWithExpiration", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\APCuL1", "fromLink": "LCache/APCuL1.html", "link": "LCache/APCuL1.html#method_isNegativeCache", "name": "LCache\\APCuL1::isNegativeCache", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\APCuL1", "fromLink": "LCache/APCuL1.html", "link": "LCache/APCuL1.html#method_getEntry", "name": "LCache\\APCuL1::getEntry", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\APCuL1", "fromLink": "LCache/APCuL1.html", "link": "LCache/APCuL1.html#method_delete", "name": "LCache\\APCuL1::delete", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\APCuL1", "fromLink": "LCache/APCuL1.html", "link": "LCache/APCuL1.html#method_getHits", "name": "LCache\\APCuL1::getHits", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\APCuL1", "fromLink": "LCache/APCuL1.html", "link": "LCache/APCuL1.html#method_getMisses", "name": "LCache\\APCuL1::getMisses", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\APCuL1", "fromLink": "LCache/APCuL1.html", "link": "LCache/APCuL1.html#method_getLastAppliedEventID", "name": "LCache\\APCuL1::getLastAppliedEventID", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\APCuL1", "fromLink": "LCache/APCuL1.html", "link": "LCache/APCuL1.html#method_setLastAppliedEventID", "name": "LCache\\APCuL1::setLastAppliedEventID", "doc": "&quot;&quot;"},
            
            {"type": "Class", "fromName": "LCache", "fromLink": "LCache.html", "link": "LCache/Address.html", "name": "LCache\\Address", "doc": "&quot;Represents a specific address in a cache, or everything in one bin,\nor everything in the entire cache.&quot;"},
                                                        {"type": "Method", "fromName": "LCache\\Address", "fromLink": "LCache/Address.html", "link": "LCache/Address.html#method___construct", "name": "LCache\\Address::__construct", "doc": "&quot;Address constructor.&quot;"},
                    {"type": "Method", "fromName": "LCache\\Address", "fromLink": "LCache/Address.html", "link": "LCache/Address.html#method_getBin", "name": "LCache\\Address::getBin", "doc": "&quot;Get the bin.&quot;"},
                    {"type": "Method", "fromName": "LCache\\Address", "fromLink": "LCache/Address.html", "link": "LCache/Address.html#method_getKey", "name": "LCache\\Address::getKey", "doc": "&quot;Get the key.&quot;"},
                    {"type": "Method", "fromName": "LCache\\Address", "fromLink": "LCache/Address.html", "link": "LCache/Address.html#method_isEntireBin", "name": "LCache\\Address::isEntireBin", "doc": "&quot;Return true if address refers to everything in the entire bin.&quot;"},
                    {"type": "Method", "fromName": "LCache\\Address", "fromLink": "LCache/Address.html", "link": "LCache/Address.html#method_isEntireCache", "name": "LCache\\Address::isEntireCache", "doc": "&quot;Return true if address refers to everything in the entire cache.&quot;"},
                    {"type": "Method", "fromName": "LCache\\Address", "fromLink": "LCache/Address.html", "link": "LCache/Address.html#method_isMatch", "name": "LCache\\Address::isMatch", "doc": "&quot;Return true if this object refers to any of the same objects as the\nprovided Address object.&quot;"},
                    {"type": "Method", "fromName": "LCache\\Address", "fromLink": "LCache/Address.html", "link": "LCache/Address.html#method_serialize", "name": "LCache\\Address::serialize", "doc": "&quot;Serialize this object, returning a string representing this address.&quot;"},
                    {"type": "Method", "fromName": "LCache\\Address", "fromLink": "LCache/Address.html", "link": "LCache/Address.html#method_unserialize", "name": "LCache\\Address::unserialize", "doc": "&quot;Unpack a serialized Address into this object.&quot;"},
            
            {"type": "Class", "fromName": "LCache", "fromLink": "LCache.html", "link": "LCache/DatabaseL2.html", "name": "LCache\\DatabaseL2", "doc": "&quot;&quot;"},
                                                        {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method___construct", "name": "LCache\\DatabaseL2::__construct", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method___destruct", "name": "LCache\\DatabaseL2::__destruct", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_countGarbage", "name": "LCache\\DatabaseL2::countGarbage", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_collectGarbage", "name": "LCache\\DatabaseL2::collectGarbage", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_getErrors", "name": "LCache\\DatabaseL2::getErrors", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_getEntry", "name": "LCache\\DatabaseL2::getEntry", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_exists", "name": "LCache\\DatabaseL2::exists", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_debugDumpState", "name": "LCache\\DatabaseL2::debugDumpState", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_set", "name": "LCache\\DatabaseL2::set", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_delete", "name": "LCache\\DatabaseL2::delete", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_getAddressesForTag", "name": "LCache\\DatabaseL2::getAddressesForTag", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_deleteTag", "name": "LCache\\DatabaseL2::deleteTag", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_applyEvents", "name": "LCache\\DatabaseL2::applyEvents", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_getHits", "name": "LCache\\DatabaseL2::getHits", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\DatabaseL2", "fromLink": "LCache/DatabaseL2.html", "link": "LCache/DatabaseL2.html#method_getMisses", "name": "LCache\\DatabaseL2::getMisses", "doc": "&quot;&quot;"},
            
            {"type": "Class", "fromName": "LCache", "fromLink": "LCache.html", "link": "LCache/Entry.html", "name": "LCache\\Entry", "doc": "&quot;&quot;"},
                                                        {"type": "Method", "fromName": "LCache\\Entry", "fromLink": "LCache/Entry.html", "link": "LCache/Entry.html#method___construct", "name": "LCache\\Entry::__construct", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Entry", "fromLink": "LCache/Entry.html", "link": "LCache/Entry.html#method_getAddress", "name": "LCache\\Entry::getAddress", "doc": "&quot;Return the Address for this entry.&quot;"},
                    {"type": "Method", "fromName": "LCache\\Entry", "fromLink": "LCache/Entry.html", "link": "LCache/Entry.html#method_getTTL", "name": "LCache\\Entry::getTTL", "doc": "&quot;Return the time-to-live for this entry.&quot;"},
            
            {"type": "Class", "fromName": "LCache", "fromLink": "LCache.html", "link": "LCache/Integrated.html", "name": "LCache\\Integrated", "doc": "&quot;&quot;"},
                                                        {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method___construct", "name": "LCache\\Integrated::__construct", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_set", "name": "LCache\\Integrated::set", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_getEntry", "name": "LCache\\Integrated::getEntry", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_get", "name": "LCache\\Integrated::get", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_exists", "name": "LCache\\Integrated::exists", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_delete", "name": "LCache\\Integrated::delete", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_deleteTag", "name": "LCache\\Integrated::deleteTag", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_synchronize", "name": "LCache\\Integrated::synchronize", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_getHitsL1", "name": "LCache\\Integrated::getHitsL1", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_getHitsL2", "name": "LCache\\Integrated::getHitsL2", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_getMisses", "name": "LCache\\Integrated::getMisses", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_getLastAppliedEventID", "name": "LCache\\Integrated::getLastAppliedEventID", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_getPool", "name": "LCache\\Integrated::getPool", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\Integrated", "fromLink": "LCache/Integrated.html", "link": "LCache/Integrated.html#method_collectGarbage", "name": "LCache\\Integrated::collectGarbage", "doc": "&quot;&quot;"},
            
            {"type": "Class", "fromName": "LCache", "fromLink": "LCache.html", "link": "LCache/L1.html", "name": "LCache\\L1", "doc": "&quot;&quot;"},
                                                        {"type": "Method", "fromName": "LCache\\L1", "fromLink": "LCache/L1.html", "link": "LCache/L1.html#method___construct", "name": "LCache\\L1::__construct", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L1", "fromLink": "LCache/L1.html", "link": "LCache/L1.html#method_getLastAppliedEventID", "name": "LCache\\L1::getLastAppliedEventID", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L1", "fromLink": "LCache/L1.html", "link": "LCache/L1.html#method_setLastAppliedEventID", "name": "LCache\\L1::setLastAppliedEventID", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L1", "fromLink": "LCache/L1.html", "link": "LCache/L1.html#method_getPool", "name": "LCache\\L1::getPool", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L1", "fromLink": "LCache/L1.html", "link": "LCache/L1.html#method_set", "name": "LCache\\L1::set", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L1", "fromLink": "LCache/L1.html", "link": "LCache/L1.html#method_isNegativeCache", "name": "LCache\\L1::isNegativeCache", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L1", "fromLink": "LCache/L1.html", "link": "LCache/L1.html#method_getKeyOverhead", "name": "LCache\\L1::getKeyOverhead", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L1", "fromLink": "LCache/L1.html", "link": "LCache/L1.html#method_setWithExpiration", "name": "LCache\\L1::setWithExpiration", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L1", "fromLink": "LCache/L1.html", "link": "LCache/L1.html#method_delete", "name": "LCache\\L1::delete", "doc": "&quot;&quot;"},
            
            {"type": "Class", "fromName": "LCache", "fromLink": "LCache.html", "link": "LCache/L2.html", "name": "LCache\\L2", "doc": "&quot;&quot;"},
                                                        {"type": "Method", "fromName": "LCache\\L2", "fromLink": "LCache/L2.html", "link": "LCache/L2.html#method_applyEvents", "name": "LCache\\L2::applyEvents", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L2", "fromLink": "LCache/L2.html", "link": "LCache/L2.html#method_set", "name": "LCache\\L2::set", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L2", "fromLink": "LCache/L2.html", "link": "LCache/L2.html#method_delete", "name": "LCache\\L2::delete", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L2", "fromLink": "LCache/L2.html", "link": "LCache/L2.html#method_deleteTag", "name": "LCache\\L2::deleteTag", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L2", "fromLink": "LCache/L2.html", "link": "LCache/L2.html#method_getAddressesForTag", "name": "LCache\\L2::getAddressesForTag", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L2", "fromLink": "LCache/L2.html", "link": "LCache/L2.html#method_collectGarbage", "name": "LCache\\L2::collectGarbage", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\L2", "fromLink": "LCache/L2.html", "link": "LCache/L2.html#method_countGarbage", "name": "LCache\\L2::countGarbage", "doc": "&quot;&quot;"},
            
            {"type": "Class", "fromName": "LCache", "fromLink": "LCache.html", "link": "LCache/LX.html", "name": "LCache\\LX", "doc": "&quot;&quot;"},
                                                        {"type": "Method", "fromName": "LCache\\LX", "fromLink": "LCache/LX.html", "link": "LCache/LX.html#method_getEntry", "name": "LCache\\LX::getEntry", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\LX", "fromLink": "LCache/LX.html", "link": "LCache/LX.html#method_getHits", "name": "LCache\\LX::getHits", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\LX", "fromLink": "LCache/LX.html", "link": "LCache/LX.html#method_getMisses", "name": "LCache\\LX::getMisses", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\LX", "fromLink": "LCache/LX.html", "link": "LCache/LX.html#method_get", "name": "LCache\\LX::get", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\LX", "fromLink": "LCache/LX.html", "link": "LCache/LX.html#method_exists", "name": "LCache\\LX::exists", "doc": "&quot;&quot;"},
            
            {"type": "Class", "fromName": "LCache", "fromLink": "LCache.html", "link": "LCache/NullL1.html", "name": "LCache\\NullL1", "doc": "&quot;&quot;"},
                                                        {"type": "Method", "fromName": "LCache\\NullL1", "fromLink": "LCache/NullL1.html", "link": "LCache/NullL1.html#method_setWithExpiration", "name": "LCache\\NullL1::setWithExpiration", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\NullL1", "fromLink": "LCache/NullL1.html", "link": "LCache/NullL1.html#method_getLastAppliedEventID", "name": "LCache\\NullL1::getLastAppliedEventID", "doc": "&quot;&quot;"},
            
            {"type": "Class", "fromName": "LCache", "fromLink": "LCache.html", "link": "LCache/StaticL1.html", "name": "LCache\\StaticL1", "doc": "&quot;&quot;"},
                                                        {"type": "Method", "fromName": "LCache\\StaticL1", "fromLink": "LCache/StaticL1.html", "link": "LCache/StaticL1.html#method___construct", "name": "LCache\\StaticL1::__construct", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL1", "fromLink": "LCache/StaticL1.html", "link": "LCache/StaticL1.html#method_getKeyOverhead", "name": "LCache\\StaticL1::getKeyOverhead", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL1", "fromLink": "LCache/StaticL1.html", "link": "LCache/StaticL1.html#method_setWithExpiration", "name": "LCache\\StaticL1::setWithExpiration", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL1", "fromLink": "LCache/StaticL1.html", "link": "LCache/StaticL1.html#method_isNegativeCache", "name": "LCache\\StaticL1::isNegativeCache", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL1", "fromLink": "LCache/StaticL1.html", "link": "LCache/StaticL1.html#method_getEntry", "name": "LCache\\StaticL1::getEntry", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL1", "fromLink": "LCache/StaticL1.html", "link": "LCache/StaticL1.html#method_delete", "name": "LCache\\StaticL1::delete", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL1", "fromLink": "LCache/StaticL1.html", "link": "LCache/StaticL1.html#method_getHits", "name": "LCache\\StaticL1::getHits", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL1", "fromLink": "LCache/StaticL1.html", "link": "LCache/StaticL1.html#method_getMisses", "name": "LCache\\StaticL1::getMisses", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL1", "fromLink": "LCache/StaticL1.html", "link": "LCache/StaticL1.html#method_getLastAppliedEventID", "name": "LCache\\StaticL1::getLastAppliedEventID", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL1", "fromLink": "LCache/StaticL1.html", "link": "LCache/StaticL1.html#method_setLastAppliedEventID", "name": "LCache\\StaticL1::setLastAppliedEventID", "doc": "&quot;&quot;"},
            
            {"type": "Class", "fromName": "LCache", "fromLink": "LCache.html", "link": "LCache/StaticL2.html", "name": "LCache\\StaticL2", "doc": "&quot;&quot;"},
                                                        {"type": "Method", "fromName": "LCache\\StaticL2", "fromLink": "LCache/StaticL2.html", "link": "LCache/StaticL2.html#method___construct", "name": "LCache\\StaticL2::__construct", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL2", "fromLink": "LCache/StaticL2.html", "link": "LCache/StaticL2.html#method_countGarbage", "name": "LCache\\StaticL2::countGarbage", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL2", "fromLink": "LCache/StaticL2.html", "link": "LCache/StaticL2.html#method_collectGarbage", "name": "LCache\\StaticL2::collectGarbage", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL2", "fromLink": "LCache/StaticL2.html", "link": "LCache/StaticL2.html#method_getEntry", "name": "LCache\\StaticL2::getEntry", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL2", "fromLink": "LCache/StaticL2.html", "link": "LCache/StaticL2.html#method_set", "name": "LCache\\StaticL2::set", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL2", "fromLink": "LCache/StaticL2.html", "link": "LCache/StaticL2.html#method_delete", "name": "LCache\\StaticL2::delete", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL2", "fromLink": "LCache/StaticL2.html", "link": "LCache/StaticL2.html#method_getAddressesForTag", "name": "LCache\\StaticL2::getAddressesForTag", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL2", "fromLink": "LCache/StaticL2.html", "link": "LCache/StaticL2.html#method_deleteTag", "name": "LCache\\StaticL2::deleteTag", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL2", "fromLink": "LCache/StaticL2.html", "link": "LCache/StaticL2.html#method_applyEvents", "name": "LCache\\StaticL2::applyEvents", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL2", "fromLink": "LCache/StaticL2.html", "link": "LCache/StaticL2.html#method_getHits", "name": "LCache\\StaticL2::getHits", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\StaticL2", "fromLink": "LCache/StaticL2.html", "link": "LCache/StaticL2.html#method_getMisses", "name": "LCache\\StaticL2::getMisses", "doc": "&quot;&quot;"},
            
            {"type": "Class", "fromName": "LCache", "fromLink": "LCache.html", "link": "LCache/UnserializationException.html", "name": "LCache\\UnserializationException", "doc": "&quot;&quot;"},
                                                        {"type": "Method", "fromName": "LCache\\UnserializationException", "fromLink": "LCache/UnserializationException.html", "link": "LCache/UnserializationException.html#method___construct", "name": "LCache\\UnserializationException::__construct", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\UnserializationException", "fromLink": "LCache/UnserializationException.html", "link": "LCache/UnserializationException.html#method___toString", "name": "LCache\\UnserializationException::__toString", "doc": "&quot;&quot;"},
                    {"type": "Method", "fromName": "LCache\\UnserializationException", "fromLink": "LCache/UnserializationException.html", "link": "LCache/UnserializationException.html#method_getSerializedData", "name": "LCache\\UnserializationException::getSerializedData", "doc": "&quot;&quot;"},
            
            
                                        // Fix trailing commas in the index
        {}
    ];

    /** Tokenizes strings by namespaces and functions */
    function tokenizer(term) {
        if (!term) {
            return [];
        }

        var tokens = [term];
        var meth = term.indexOf('::');

        // Split tokens into methods if "::" is found.
        if (meth > -1) {
            tokens.push(term.substr(meth + 2));
            term = term.substr(0, meth - 2);
        }

        // Split by namespace or fake namespace.
        if (term.indexOf('\\') > -1) {
            tokens = tokens.concat(term.split('\\'));
        } else if (term.indexOf('_') > 0) {
            tokens = tokens.concat(term.split('_'));
        }

        // Merge in splitting the string by case and return
        tokens = tokens.concat(term.match(/(([A-Z]?[^A-Z]*)|([a-z]?[^a-z]*))/g).slice(0,-1));

        return tokens;
    };

    root.Sami = {
        /**
         * Cleans the provided term. If no term is provided, then one is
         * grabbed from the query string "search" parameter.
         */
        cleanSearchTerm: function(term) {
            // Grab from the query string
            if (typeof term === 'undefined') {
                var name = 'search';
                var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
                var results = regex.exec(location.search);
                if (results === null) {
                    return null;
                }
                term = decodeURIComponent(results[1].replace(/\+/g, " "));
            }

            return term.replace(/<(?:.|\n)*?>/gm, '');
        },

        /** Searches through the index for a given term */
        search: function(term) {
            // Create a new search index if needed
            if (!bhIndex) {
                bhIndex = new Bloodhound({
                    limit: 500,
                    local: searchIndex,
                    datumTokenizer: function (d) {
                        return tokenizer(d.name);
                    },
                    queryTokenizer: Bloodhound.tokenizers.whitespace
                });
                bhIndex.initialize();
            }

            results = [];
            bhIndex.get(term, function(matches) {
                results = matches;
            });

            if (!rootPath) {
                return results;
            }

            // Fix the element links based on the current page depth.
            return $.map(results, function(ele) {
                if (ele.link.indexOf('..') > -1) {
                    return ele;
                }
                ele.link = rootPath + ele.link;
                if (ele.fromLink) {
                    ele.fromLink = rootPath + ele.fromLink;
                }
                return ele;
            });
        },

        /** Get a search class for a specific type */
        getSearchClass: function(type) {
            return searchTypeClasses[type] || searchTypeClasses['_'];
        },

        /** Add the left-nav tree to the site */
        injectApiTree: function(ele) {
            ele.html(treeHtml);
        }
    };

    $(function() {
        // Modify the HTML to work correctly based on the current depth
        rootPath = $('body').attr('data-root-path');
        treeHtml = treeHtml.replace(/href="/g, 'href="' + rootPath);
        Sami.injectApiTree($('#api-tree'));
    });

    return root.Sami;
})(window);

$(function() {

    // Enable the version switcher
    $('#version-switcher').change(function() {
        window.location = $(this).val()
    });

    
        // Toggle left-nav divs on click
        $('#api-tree .hd span').click(function() {
            $(this).parent().parent().toggleClass('opened');
        });

        // Expand the parent namespaces of the current page.
        var expected = $('body').attr('data-name');

        if (expected) {
            // Open the currently selected node and its parents.
            var container = $('#api-tree');
            var node = $('#api-tree li[data-name="' + expected + '"]');
            // Node might not be found when simulating namespaces
            if (node.length > 0) {
                node.addClass('active').addClass('opened');
                node.parents('li').addClass('opened');
                var scrollPos = node.offset().top - container.offset().top + container.scrollTop();
                // Position the item nearer to the top of the screen.
                scrollPos -= 200;
                container.scrollTop(scrollPos);
            }
        }

    
    
        var form = $('#search-form .typeahead');
        form.typeahead({
            hint: true,
            highlight: true,
            minLength: 1
        }, {
            name: 'search',
            displayKey: 'name',
            source: function (q, cb) {
                cb(Sami.search(q));
            }
        });

        // The selection is direct-linked when the user selects a suggestion.
        form.on('typeahead:selected', function(e, suggestion) {
            window.location = suggestion.link;
        });

        // The form is submitted when the user hits enter.
        form.keypress(function (e) {
            if (e.which == 13) {
                $('#search-form').submit();
                return true;
            }
        });

    
});


