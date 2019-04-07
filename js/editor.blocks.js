
var iconptwsgallery = wp.element.createElement(
    'svg', 
    { width: '20px', height: '20px', viewBox: '0 0 100 100', xmlns: 'http://www.w3.org/2000/svg' },
    wp.element.createElement('path', { d: 'm50 20c4.1367 0 7.5-3.3633 7.5-7.5s-3.3633-7.5-7.5-7.5-7.5 3.3633-7.5 7.5 3.3633 7.5 7.5 7.5zm0-10c1.3789 0 2.5 1.1211 2.5 2.5s-1.1211 2.5-2.5 2.5-2.5-1.1211-2.5-2.5 1.1211-2.5 2.5-2.5z' }),
    wp.element.createElement('path', { d: 'm53.535 42.93l-3.5352 3.5352-3.5352-3.5352-3.5352 3.5352 3.5352 3.5352-3.5352 3.5352 3.5352 3.5352 3.5352-3.5352 3.5352 3.5352 3.5352-3.5352-3.5352-3.5352 3.5352-3.5352z' }),
    wp.element.createElement('path', { d: 'm48.75 87.715l-3.2305-3.2344-3.5391 3.5391 6.7695 6.7656 9.2695-9.2656-3.5391-3.5391z' }),
    wp.element.createElement('path', { d: 'm67.5 12.5c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm77.051 12.5c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm86.109 15.281c0 3.332-5 3.332-5 0 0-3.3359 5-3.3359 5 0' }),
    wp.element.createElement('path', { d: 'm92.617 22.098c0 3.332-5 3.332-5 0 0-3.3359 5-3.3359 5 0' }),
    wp.element.createElement('path', { d: 'm95 31.23c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm92.617 40.371c0 3.3359-5 3.3359-5 0 0-3.332 5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm86.109 47.199c0 3.3359-5 3.3359-5 0 0-3.332 5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm77.051 49.98c0 3.332-5 3.332-5 0 0-3.3359 5-3.3359 5 0' }),
    wp.element.createElement('path', { d: 'm67.5 50.02c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm37.5 50c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm27.91 50c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm18.922 52.797c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm12.363 59.629c0 3.332-5 3.332-5 0 0-3.3359 5-3.3359 5 0' }),
    wp.element.createElement('path', { d: 'm10 68.77c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm12.383 77.902c0 3.332-5 3.332-5 0 0-3.332 5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm18.922 84.719c0 3.3359-5 3.3359-5 0 0-3.332 5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm27.949 87.5c0 3.332-5 3.332-5 0s5-3.332 5 0' }),
    wp.element.createElement('path', { d: 'm37.5 87.516c0 3.332-5 3.332-5 0s5-3.332 5 0' })
); 


wp.blocks.registerBlockType('ptws/gallery', {
    title: 'PTWS Gallery',
    description: 'A server-side assembled gallery of images with various options.',
    icon: {
        background: 'rgba(224, 243, 254, 0.52)',
        src: iconptwsgallery
    },
    category: 'widgets',
    edit: wp.data.withSelect(function (select) {
        return {
            posts: select('core').getEntityRecords('postType', 'post', { per_page: 3 })
        };
    })(function (_ref) {
        var posts = _ref.posts;
        var className = _ref.className;
        var isSelected = _ref.isSelected;
        var setAttributes = _ref.setAttributes;

        if (!posts) {
            return wp.element.createElement('p',
                { className: className },
                wp.element.createElement(wp.components.Spinner, null),
                'Loading Posts'
            );
        }
        if (0 === posts.length) {
            return wp.element.createElement('p',
                null,
                'No Posts'
            );
        }
        return wp.element.createElement(
            'ul',
            { className: className },
            posts.map(function (post) {
                return wp.element.createElement(
                    'li',
                    null,
                    wp.element.createElement('a',
                        { className: className, href: post.link },
                        post.title.rendered
                    )
                );
            })
        );
    }) // end withAPIData
    , // end edit
    save: function save() {
        // We are rendering in PHP
        return null;
    }
});

