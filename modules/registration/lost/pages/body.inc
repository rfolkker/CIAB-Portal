<div id="page" class="UI-container">
    <div id="main_content" class="UI-maincontent">
        <div class="UI-event-sectionbar">
            <span>Welcome <?= $_SESSION['preferredName'] ?></span>
        </div>

        <div class="UI-container UI-padding UI-hide" id="lost">
            <div class="UI-event-sectionbar">Report Lost Badges</div>
        </div>

        <!-- TODO: These are temporary and not-smart links to the other pages -->
        <div><a href="/index.php?Function=registration/checkin">Registration Check-in</a></div>
        <div><a href="/index.php?Function=registration/boarding">Display Boarding Passes</a></div>
    </div>
</div>