// module.exports = {
//     plugins: [
//       require('postcss-prefix-selector')({
//         prefix: '#TFIP-style',
//         transform: function (prefix, selector, prefixedSelector) {
//           // Avoid prefixing key tags like body/html
//           if (selector.startsWith('html') || selector.startsWith('body')) {
//             return selector;
//           }
//           return prefixedSelector;
//         }
//       })
//     ]
//   };
module.exports = {
    plugins: [
        require('postcss-prefix-selector')({
            prefix: '.TFIP-style',
            transform: function (prefix, selector, prefixedSelector) {
              if (selector.startsWith('html') || selector.startsWith('body')) {
                return selector;
              }
              return prefixedSelector;
            }
          })
    ]
  };
