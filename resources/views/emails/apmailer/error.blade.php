
<style type="text/css">
    .header {
        background: #8a8a8a;
    }
    .header .columns {
        padding-bottom: 0;
    }
    .header p {
        color: #fff;
        margin-bottom: 0;
    }
    .header .wrapper-inner {
        padding: 20px; /*controls the height of the header*/
    }
    .header .container {
        background: #8a8a8a;
    }
    .wrapper.secondary {
        background: #f3f3f3;
    }
</style>
<!-- move the above styles into your custom stylesheet -->


<wrapper class="header" bgcolor="#8a8a8a">
    <container>
        <row class="collapse">
            <columns small="6" valign="middle">
                <img src="http://placehold.it/200x50/663399">
            </columns>
            <columns small="6" valign="middle">
                <p class="text-right">BASIC</p>
            </columns>
        </row>
    </container>
</wrapper>

<container>

    <spacer size="16"></spacer>

    <row>
        <columns>

            <h1>Hi, Susan Calvin</h1>
            <p class="lead">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Magni, iste, amet consequatur a veniam.</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ut optio nulla et, fugiat. Maiores accusantium nostrum asperiores provident, quam modi ex inventore dolores id aspernatur architecto odio minima perferendis, explicabo. Lorem ipsum dolor sit amet, consectetur adipisicing elit. Minima quos quasi itaque beatae natus fugit provident delectus, magnam laudantium odio corrupti sit quam. Optio aut ut repudiandae velit distinctio asperiores?</p>
            <callout class="primary">
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Reprehenderit repellendus natus, sint ea optio dignissimos asperiores inventore a molestiae dolorum placeat repellat excepturi mollitia ducimus unde doloremque ad, alias eos!</p>
            </callout>

        </columns>
    </row>

    <wrapper class="secondary">
        <spacer size="16"></spacer>
        <row>
            <columns small="12" large="6">
                <h5>Connect With Us:</h5>
                <menu class="vertical">
                    <item style="text-align: left;" href="#">Twitter</item>
                    <item style="text-align: left;" href="#">Facebook</item>
                    <item style="text-align: left;" href="#">Google +</item>
                </menu>
            </columns>
            <columns small="12" large="6">
                <h5>Contact Info:</h5>
                <p>Phone: 408-341-0600</p>
                <p>Email: <a href="mailto:foundation@zurb.com">foundation@zurb.com</a></p>
            </columns>
        </row>
    </wrapper>

</container>
